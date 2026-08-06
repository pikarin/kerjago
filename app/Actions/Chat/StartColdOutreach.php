<?php

namespace App\Actions\Chat;

use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Chat\Actions\OpenConversation;
use App\Chat\Data\NewConversation;
use App\Chat\Models\Conversation;
use App\Enums\ConversationKind;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class StartColdOutreach
{
    public function __construct(
        private OpenConversation $openConversation,
        private ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
    ) {}

    /**
     * An employer opens a conversation with a jobseeker found via Talent
     * Search. No Application is involved, so the conversation carries no
     * context.
     *
     * The gate this method was built to hold is now in place: an employer may
     * only cold-message a candidate they hold an active Candidate Unlock for
     * (ADR 0013). Chat is a contact channel, so leaving it open would route
     * straight around the mask. There is still no consent flag and no rate
     * limit — an unlocked candidate may be messaged without limit.
     *
     * @throws AuthorizationException when the employer has no active unlock
     *
     * Idempotent per employer/jobseeker pair, so repeated clicks reopen the
     * existing thread instead of fragmenting the history.
     */
    public function handle(User $employer, JobseekerProfile $target): Conversation
    {
        $employerProfile = $employer->employerProfile;

        if ($employerProfile === null || ! $this->resolveUnlockedProfileIds->has($employerProfile, $target->id)) {
            throw new AuthorizationException(__('Unlock this candidate before starting a conversation.'));
        }

        return $this->openConversation->handle(new NewConversation(
            kind: ConversationKind::ColdOutreach->value,
            createdByParticipantId: $employer->id,
            participantIds: [$employer->id, $target->user_id],
            uniqueKey: sprintf(
                '%s:%s:%s',
                ConversationKind::ColdOutreach->value,
                $employer->id,
                $target->user_id,
            ),
        ));
    }
}
