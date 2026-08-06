<?php

namespace App\Actions\Unlocks;

use App\Chat\Actions\RestoreParticipant;
use App\Chat\Models\Conversation;
use App\Enums\ChatContextType;
use App\Enums\UnlockSource;
use App\Models\Application;
use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Grant one employer sight of one candidate until `expires_at`, and open the
 * chat that was being withheld.
 *
 * Idempotent by the unique index on (employer_profile_id, jobseeker_profile_id):
 * a second unlock for the same pair raises the existing expiry to whichever date
 * is later and leaves everything else alone. Longest wins, so an unlock earned
 * from a second job can extend access but never shorten it.
 */
class IssueCandidateUnlock
{
    public function __construct(private RestoreParticipant $restoreParticipant) {}

    public function handle(
        EmployerProfile $employerProfile,
        JobseekerProfile $jobseekerProfile,
        CarbonInterface $expiresAt,
        UnlockSource $source = UnlockSource::AutoFirstTen,
        ?Job $job = null,
    ): CandidateUnlock {
        $unlock = DB::transaction(function () use ($employerProfile, $jobseekerProfile, $expiresAt, $source, $job): CandidateUnlock {
            $existing = CandidateUnlock::query()
                ->where('employer_profile_id', $employerProfile->id)
                ->where('jobseeker_profile_id', $jobseekerProfile->id)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ($expiresAt->greaterThan($existing->expires_at)) {
                    $existing->update(['expires_at' => $expiresAt]);
                }

                return $existing;
            }

            return CandidateUnlock::query()->create([
                'employer_profile_id' => $employerProfile->id,
                'jobseeker_profile_id' => $jobseekerProfile->id,
                'job_id' => $job?->id,
                'source' => $source,
                'expires_at' => $expiresAt,
            ]);
        });

        $this->openWithheldConversations($employerProfile, $jobseekerProfile);

        return $unlock;
    }

    /**
     * Application threads for this pair were opened with the employer's access
     * withheld. Unlocking is what hands it over — including the history written
     * while they were locked out.
     */
    private function openWithheldConversations(EmployerProfile $employerProfile, JobseekerProfile $jobseekerProfile): void
    {
        $applicationIds = array_values(Application::query()
            ->where('jobseeker_profile_id', $jobseekerProfile->id)
            ->whereHas('job', fn (Builder $query) => $query->where('employer_profile_id', $employerProfile->id))
            ->get(['id'])
            ->map(fn (Application $application): string => $application->id)
            ->all());

        if ($applicationIds === []) {
            return;
        }

        $conversationIds = array_values(Conversation::query()
            ->where('context_type', ChatContextType::Application->value)
            ->whereIn('context_id', $applicationIds)
            ->get(['id'])
            ->map(fn (Conversation $conversation): string => $conversation->id)
            ->all());

        $this->restoreParticipant->handle($employerProfile->user_id, $conversationIds);
    }
}
