<?php

namespace App\Actions\Verification;

use App\Enums\JobStatus;
use App\Enums\VerificationDecision;
use App\Enums\VerificationSource;
use App\Models\EmployerProfile;
use App\Models\EmployerVerificationEvent;
use App\Models\Job;
use App\Models\User;
use App\Notifications\EmployerUnverified;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Take a company's standing away, and take its ads down with it.
 *
 * Live ads are pulled back to Pending rather than left to expire: the realistic
 * reason to revoke is that the company should not be reaching candidates, and
 * its live listings are the main way it does.
 *
 * `expires_at` is left alone, so a company revoked in error and restored comes
 * back on its remaining days — neither refilled nor frozen. Status is editable
 * and the timestamps are not; making revocation the one status change that
 * moves the clock would break the Draft round trip PublishJob relies on.
 *
 * Applications already received, Candidate Unlocks already issued and resume
 * snapshots already taken are untouched. Those are facts about candidates, not
 * capabilities of the employer.
 */
class UnverifyEmployer
{
    public function handle(
        EmployerProfile $employerProfile,
        string $reason,
        ?User $actor = null,
        VerificationSource $source = VerificationSource::Staff,
        ?string $employerMessage = null,
    ): EmployerProfile {
        // `required` accepts a string of spaces, so the invariant is enforced
        // here rather than trusted to whatever form happened to submit it: an
        // audit row whose reason is blank records that something happened and
        // nothing about why.
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Withdrawing verification requires a reason.');
        }

        $batchId = null;

        $revoked = DB::transaction(function () use ($employerProfile, $reason, $actor, $source, $employerMessage, &$batchId): bool {
            $locked = EmployerProfile::query()
                ->whereKey($employerProfile->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isVerified()) {
                return false;
            }

            // Read under the lock rather than off the caller's copy: a
            // verification that landed after this profile was hydrated would
            // otherwise leave us cancelling nothing, and its batch would keep
            // draining.
            $batchId = $locked->publish_batch_id;

            $locked->forceFill([
                'verified_at' => null,
                'verified_by_id' => null,
                'publish_batch_id' => null,
            ])->save();

            EmployerVerificationEvent::query()->create([
                'employer_profile_id' => $locked->id,
                'decision' => VerificationDecision::Unverified,
                'source' => $source,
                'actor_id' => $actor?->id,
                'reason' => $reason,
                'employer_message' => $employerMessage,
            ]);

            $employerProfile->setRawAttributes($locked->getAttributes(), sync: true);

            return true;
        });

        if (! $revoked) {
            return $employerProfile;
        }

        // Cancelled before the ads are pulled down, so a batch still draining
        // cannot publish behind the sweep below.
        if ($batchId !== null) {
            Bus::findBatch($batchId)?->cancel();
        }

        $this->parkLiveJobs($employerProfile);

        $employerProfile->user->notify(new EmployerUnverified($employerProfile, $employerMessage));

        return $employerProfile;
    }

    /**
     * Pull every live ad back to Pending, one save at a time.
     *
     * Per model, never a mass update: the save is what tells Scout to drop the
     * document, and a query-builder update would leave the ad in Typesense —
     * gone from the database's point of view, still findable in search.
     *
     * Runs **after** the revocation commits, never inside it. Scout is
     * configured to index synchronously, so each save is a blocking HTTP call
     * to Typesense; doing that under the profile's row lock would hold the lock
     * for as long as the search engine takes to answer, and a timeout partway
     * through would roll the revocation back while leaving the documents it had
     * already deleted gone from the index. `jobs:expire` keeps its per-model
     * loop outside a transaction for the same reason.
     *
     * Running after the commit is also what closes the window on a worker that
     * read the profile mid-transaction, saw it still verified and published:
     * that ad is Active by the time this sweep runs, so the sweep takes it back
     * down.
     *
     * Keyed rather than offset-paged, for the same reason `jobs:expire` is: the
     * callback changes the very column the query filters on, so an offset-based
     * chunk would step past everything the previous page shifted out of the
     * result set and leave later ads live and indexed.
     */
    private function parkLiveJobs(EmployerProfile $employerProfile): void
    {
        $employerProfile->jobs()
            ->where('status', JobStatus::Active)
            ->chunkById(200, function (Collection $jobs): void {
                $jobs->each(function (Job $job): void {
                    $job->forceFill(['status' => JobStatus::Pending])->save();
                });
            });
    }
}
