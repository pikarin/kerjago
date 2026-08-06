<?php

namespace App\Console\Commands;

use App\Chat\Actions\RevokeParticipant;
use App\Chat\Models\Conversation;
use App\Enums\ChatContextType;
use App\Models\Application;
use App\Models\CandidateUnlock;
use App\Support\Chat\ColdOutreachKey;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ExpireCandidateUnlocks extends Command
{
    protected $signature = 'unlocks:expire';

    protected $description = 'Re-lock candidates whose unlock has run out and close the employer out of their threads';

    /**
     * Masking itself needs no sweep — every read path already filters on
     * `expires_at`, so a candidate re-masks the moment their unlock lapses.
     * What this command does is the part no read path can do: take the
     * employer back out of the conversations the unlock let them into.
     *
     * The unlock rows are kept, not deleted. A job's ten slots are spent when
     * issued, and deleting the evidence would hand them back.
     */
    public function handle(RevokeParticipant $revokeParticipant): int
    {
        $revoked = 0;

        CandidateUnlock::query()
            ->where('expires_at', '<=', now())
            // Without this the sweep would re-examine every unlock that has
            // ever expired, on every run, forever — three queries apiece for
            // work already done.
            ->whereNull('revoked_at')
            ->with(['employerProfile', 'jobseekerProfile'])
            ->chunkById(100, function (Collection $unlocks) use ($revokeParticipant, &$revoked): void {
                /** @var Collection<int, CandidateUnlock> $unlocks */
                foreach ($unlocks as $unlock) {
                    $revoked += $this->sweep($unlock, $revokeParticipant);
                }
            });

        $this->info("Revoked chat access on {$revoked} conversation(s).");

        return self::SUCCESS;
    }

    /**
     * Retire one expired unlock: claim the row, then take the employer out of
     * the pair's threads.
     *
     * Both happen inside one transaction, under a lock on the unlock row that
     * IssueCandidateUnlock takes as well. Claiming alone was not enough — a
     * renewal landing between the claim and the revoke would restore the
     * employer and then have that undone a statement later, leaving an active
     * unlock with no thread access, no teaser, and nothing to heal it. Holding
     * the lock across both makes the two operations take turns.
     *
     * The row itself is never deleted: a slot is spent when it is issued, and
     * deleting the evidence would hand it back.
     *
     * @return int the number of conversations the employer was removed from
     */
    private function sweep(CandidateUnlock $unlock, RevokeParticipant $revokeParticipant): int
    {
        return DB::transaction(function () use ($unlock, $revokeParticipant): int {
            $claimed = CandidateUnlock::query()
                ->whereKey($unlock->id)
                ->lockForUpdate()
                // Re-asserts what the chunk was selected on, so a row renewed
                // since the read updates nothing and is skipped.
                ->where('expires_at', '<=', now())
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            if ($claimed !== 1) {
                return 0;
            }

            return $revokeParticipant->handle(
                $unlock->employerProfile->user_id,
                $this->conversationIdsFor($unlock),
            );
        });
    }

    /**
     * @return list<string>
     */
    private function conversationIdsFor(CandidateUnlock $unlock): array
    {
        $applicationIds = array_values(Application::query()
            ->where('jobseeker_profile_id', $unlock->jobseeker_profile_id)
            ->whereHas('job', fn (Builder $query) => $query->where('employer_profile_id', $unlock->employer_profile_id))
            ->get(['id'])
            ->map(fn (Application $application): string => $application->id)
            ->all());

        // Cold outreach carries no context, so it is found by the unique key
        // StartColdOutreach composes for the pair. Must stay in step with
        // IssueCandidateUnlock, which restores by the same key.
        $coldOutreachKey = ColdOutreachKey::for($unlock->employerProfile, $unlock->jobseekerProfile);

        return array_values(Conversation::query()
            ->where(fn (Builder $query) => $query
                ->where('context_type', ChatContextType::Application->value)
                ->whereIn('context_id', $applicationIds))
            ->orWhere('unique_key', $coldOutreachKey)
            ->get(['id'])
            ->map(fn (Conversation $conversation): string => $conversation->id)
            ->all());
    }
}
