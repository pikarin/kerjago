<?php

namespace App\Actions\Verification;

use App\Enums\JobStatus;
use App\Enums\VerificationDecision;
use App\Enums\VerificationSource;
use App\Jobs\PublishPendingJob;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\Job;
use App\Models\User;
use App\Notifications\EmployerVerified;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

/**
 * Mark a company verified, and put live everything it has been holding.
 *
 * Idempotent: verifying an already-verified company is a no-op, which matters
 * because the alternative is a second publish batch racing the first.
 */
class VerifyEmployer
{
    public function handle(
        EmployerProfile $employerProfile,
        ?User $actor = null,
        VerificationSource $source = VerificationSource::Staff,
        ?string $employerMessage = null,
    ): EmployerProfile {
        $verified = DB::transaction(function () use ($employerProfile, $actor, $source, $employerMessage): bool {
            // Locked for the same reason the unlock upsert locks: two staff
            // members clicking Verify at once would otherwise both find an
            // unverified company and both dispatch a batch.
            $locked = EmployerProfile::query()
                ->whereKey($employerProfile->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->isVerified()) {
                return false;
            }

            $locked->forceFill([
                'verified_at' => now(),
                'verified_by_id' => $actor?->id,
                // The request has been answered. Left set, it would keep the
                // company in the Admingo queue and keep aging its "waiting N
                // days" number.
                'verification_requested_at' => null,
            ])->save();

            EmployerVerificationEvent::query()->create([
                'employer_profile_id' => $locked->id,
                'decision' => VerificationDecision::Verified,
                'source' => $source,
                'actor_id' => $actor?->id,
                'employer_message' => $employerMessage,
            ]);

            $employerProfile->setRawAttributes($locked->getAttributes(), sync: true);

            return true;
        });

        if (! $verified) {
            return $employerProfile;
        }

        // Dispatched after the state is committed, not inside the transaction:
        // a batch composed inside would have its rows written before the
        // verification itself, and a worker could pick a job up and find the
        // company still unverified.
        $this->publishPendingJobs($employerProfile);

        $employerProfile->user->notify(new EmployerVerified($employerProfile));

        return $employerProfile;
    }

    /**
     * Queue one job per parked ad and record the batch, so Admingo can follow
     * the run rather than guess at it.
     */
    private function publishPendingJobs(EmployerProfile $employerProfile): void
    {
        $pending = $employerProfile->jobs()
            ->where('status', JobStatus::Pending)
            ->get()
            ->map(fn (Job $job): PublishPendingJob => new PublishPendingJob($job))
            ->all();

        if ($pending === []) {
            // Nothing to watch. Clearing the id stops Admingo showing progress
            // from a previous verification against this one.
            $employerProfile->forceFill(['publish_batch_id' => null])->save();

            return;
        }

        $batch = Bus::batch($pending)
            // One ad that cannot be indexed must not strand the rest, and a
            // failure is something staff should see rather than something that
            // silently halts the run.
            ->allowFailures()
            ->name('publish-pending-jobs:'.$employerProfile->id)
            ->dispatch();

        $employerProfile->forceFill(['publish_batch_id' => $batch->id])->save();
    }
}
