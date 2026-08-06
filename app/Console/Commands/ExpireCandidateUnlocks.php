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
                    // Claim before revoking, re-asserting both conditions the
                    // chunk was selected on. An unlock renewed between the read
                    // and here would otherwise be revoked straight after being
                    // restored — leaving the employer holding an active unlock
                    // but locked out of the threads — and stamped revoked, so
                    // the sweep would skip it forever when the new term lapsed.
                    if (! $this->claim($unlock)) {
                        continue;
                    }

                    // The row itself stays: a slot is spent when it is issued,
                    // and deleting the evidence would hand it back.
                    $revoked += $revokeParticipant->handle(
                        $unlock->employerProfile->user_id,
                        $this->conversationIdsFor($unlock),
                    );
                }
            });

        $this->info("Revoked chat access on {$revoked} conversation(s).");

        return self::SUCCESS;
    }

    /**
     * Take ownership of one expired unlock, atomically.
     *
     * The WHERE clause repeats the selection criteria, so a row renewed since
     * the chunk was read updates nothing and is left alone.
     */
    private function claim(CandidateUnlock $unlock): bool
    {
        return CandidateUnlock::query()
            ->whereKey($unlock->id)
            ->where('expires_at', '<=', now())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]) === 1;
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
