<?php

namespace App\Actions\Chat;

use App\Chat\Actions\OpenConversation;
use App\Chat\Data\NewConversation;
use App\Chat\Models\Conversation;
use App\Enums\ConversationKind;
use App\Models\JobseekerProfile;
use App\Models\User;

class StartColdOutreach
{
    public function __construct(private OpenConversation $openConversation) {}

    /**
     * An employer opens a conversation with a jobseeker found via Talent
     * Search. No Application is involved, so the conversation carries no
     * context.
     *
     * There is deliberately no gate here: no consent flag, no per-employer rate
     * limit, and no blocking. That was a decision taken knowingly — any
     * employer may message any jobseeker without limit. This method is the
     * single place such a gate goes when one is wanted, which is the only
     * reason cold outreach has its own Action rather than calling
     * OpenConversation from a controller.
     *
     * Idempotent per employer/jobseeker pair, so repeated clicks reopen the
     * existing thread instead of fragmenting the history.
     */
    public function handle(User $employer, JobseekerProfile $target): Conversation
    {
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
