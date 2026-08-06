<?php

namespace App\Support\Chat;

use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Models\Conversation;
use App\Chat\Models\Participant;
use App\Models\JobseekerProfile;
use App\Models\User;

/**
 * Membership, plus the Candidate Unlock rule for employers.
 *
 * Notably staff do NOT get blanket access by role: internal team see a
 * conversation by being added to it, the same as anyone else. Reading every
 * conversation on the platform would be a surveillance capability, and it is
 * not required by anything currently asked for.
 *
 * An employer's access to a jobseeker thread is already withheld structurally —
 * they join with `left_at` set, so membership alone would deny them. The unlock
 * check here is deliberate duplication: it is the rule stated once in a place
 * both the HTTP policy and the broadcast presence channel funnel through, so a
 * participant row that gets restored by mistake still does not open a locked
 * candidate's conversation.
 */
class PolicyChatAuthorizer implements ChatAuthorizer
{
    public function __construct(private ResolveUnlockedProfileIds $resolveUnlockedProfileIds) {}

    public function canAccess(string $participantId, Conversation $conversation): bool
    {
        if (! $conversation->hasParticipant($participantId)) {
            return false;
        }

        $employerProfile = User::query()->find($participantId)?->employerProfile;

        if ($employerProfile === null) {
            return true;
        }

        $counterpartProfile = $this->jobseekerCounterpart($participantId, $conversation);

        if ($counterpartProfile === null) {
            return true;
        }

        return $this->resolveUnlockedProfileIds->has($employerProfile, $counterpartProfile);
    }

    /**
     * The jobseeker profile id on the other side of the thread, or null when
     * there is no jobseeker in it (an internal staff conversation, say).
     */
    private function jobseekerCounterpart(string $participantId, Conversation $conversation): ?string
    {
        $conversation->loadMissing('participants');

        $otherIds = $conversation->participants
            ->reject(fn (Participant $participant): bool => $participant->participant_id === $participantId)
            ->map(fn (Participant $participant): string => $participant->participant_id)
            ->all();

        if ($otherIds === []) {
            return null;
        }

        return JobseekerProfile::query()
            ->whereIn('user_id', $otherIds)
            ->first(['id'])
            ?->id;
    }
}
