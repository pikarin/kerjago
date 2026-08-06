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
use App\Support\Chat\ColdOutreachKey;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
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
    public function __construct(
        private RestoreParticipant $restoreParticipant,
        private ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
    ) {}

    public function handle(
        EmployerProfile $employerProfile,
        JobseekerProfile $jobseekerProfile,
        CarbonInterface $expiresAt,
        UnlockSource $source = UnlockSource::AutoFirstTen,
        ?Job $job = null,
    ): CandidateUnlock {
        $unlock = DB::transaction(function () use ($employerProfile, $jobseekerProfile, $expiresAt, $source, $job): CandidateUnlock {
            $existing = $this->lockExisting($employerProfile, $jobseekerProfile);

            if ($existing !== null) {
                return $this->extend($existing, $expiresAt);
            }

            try {
                return CandidateUnlock::query()->create([
                    'employer_profile_id' => $employerProfile->id,
                    'jobseeker_profile_id' => $jobseekerProfile->id,
                    'job_id' => $job?->id,
                    'source' => $source,
                    'expires_at' => $expiresAt,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                // lockForUpdate takes no lock when there is no row to lock, so
                // two applications from the same candidate to two of this
                // employer's jobs can both find nothing and both insert. The
                // unique index catches the loser; without this the whole
                // application would roll back and its resume snapshot would be
                // left orphaned on disk. Same shape as OpenConversation.
                $winner = $this->lockExisting($employerProfile, $jobseekerProfile);

                if ($winner === null) {
                    throw $exception;
                }

                return $this->extend($winner, $expiresAt);
            }
        });

        // The memo is per-request and caches negatives, so a lookup taken
        // before this call would otherwise keep reporting the candidate locked
        // for the rest of the request — including to the queued job that opens
        // their conversation when the queue runs synchronously.
        $this->resolveUnlockedProfileIds->forget($employerProfile, $jobseekerProfile->id);

        $this->openWithheldConversations($employerProfile, $jobseekerProfile);

        return $unlock;
    }

    private function lockExisting(EmployerProfile $employerProfile, JobseekerProfile $jobseekerProfile): ?CandidateUnlock
    {
        return CandidateUnlock::query()
            ->where('employer_profile_id', $employerProfile->id)
            ->where('jobseeker_profile_id', $jobseekerProfile->id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Longest wins: a second unlock can push the expiry out but never pull it
     * in. Renewing past an expiry also clears `revoked_at`, or the sweep would
     * consider the pair already dealt with and never take the employer back out
     * of their threads when the new term ends.
     */
    private function extend(CandidateUnlock $unlock, CarbonInterface $expiresAt): CandidateUnlock
    {
        if ($expiresAt->greaterThan($unlock->expires_at)) {
            $unlock->forceFill([
                'expires_at' => $expiresAt,
                'revoked_at' => null,
            ])->save();
        }

        return $unlock;
    }

    /**
     * Threads for this pair the employer's access was withheld from. Unlocking
     * is what hands it over — including the history written while they were
     * locked out.
     *
     * Must mirror ExpireCandidateUnlocks::conversationIdsFor() exactly. The
     * sweep revokes cold outreach as well as application threads, so restoring
     * only the latter would strand a cold-outreach thread revoked-forever:
     * StartColdOutreach is idempotent, so it hands back the existing row rather
     * than making a fresh one, and the employer 403s on it with no way out.
     */
    private function openWithheldConversations(EmployerProfile $employerProfile, JobseekerProfile $jobseekerProfile): void
    {
        $conversationIds = $this->pairConversationIds($employerProfile, $jobseekerProfile);

        if ($conversationIds === []) {
            return;
        }

        $this->restoreParticipant->handle($employerProfile->user_id, $conversationIds);
    }

    /**
     * Every conversation that exists solely because these two are connected:
     * their application threads, plus their cold-outreach thread.
     *
     * @return list<string>
     */
    private function pairConversationIds(EmployerProfile $employerProfile, JobseekerProfile $jobseekerProfile): array
    {
        $applicationIds = array_values(Application::query()
            ->where('jobseeker_profile_id', $jobseekerProfile->id)
            ->whereHas('job', fn (Builder $query) => $query->where('employer_profile_id', $employerProfile->id))
            ->get(['id'])
            ->map(fn (Application $application): string => $application->id)
            ->all());

        return array_values(Conversation::query()
            ->where(fn (Builder $query) => $query
                ->where('context_type', ChatContextType::Application->value)
                ->whereIn('context_id', $applicationIds))
            // Cold outreach carries no context, so it is found by the unique
            // key StartColdOutreach composes for the pair.
            ->orWhere('unique_key', ColdOutreachKey::for($employerProfile, $jobseekerProfile))
            ->get(['id'])
            ->map(fn (Conversation $conversation): string => $conversation->id)
            ->all());
    }
}
