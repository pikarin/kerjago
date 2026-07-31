<?php

namespace App\Actions\Chat;

use App\Chat\Actions\OpenConversation;
use App\Chat\Data\NewConversation;
use App\Chat\Models\Conversation;
use App\Enums\ConversationKind;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class StartInternalConversation
{
    public function __construct(private OpenConversation $openConversation) {}

    /**
     * Internal Kerjago team opening a conversation with a jobseeker or an
     * employer. This is how staff gain access to a conversation — they are not
     * granted blanket read access by role (see PolicyChatAuthorizer).
     *
     * Idempotent per staff/counterpart pair.
     *
     * @throws ValidationException when the initiator is not staff
     */
    public function handle(User $staff, User $counterpart): Conversation
    {
        if (! $staff->isStaff()) {
            throw ValidationException::withMessages([
                'staff' => __('Only internal team can start an internal conversation.'),
            ]);
        }

        return $this->openConversation->handle(new NewConversation(
            kind: ConversationKind::Internal->value,
            createdByParticipantId: $staff->id,
            participantIds: [$staff->id, $counterpart->id],
            uniqueKey: sprintf(
                '%s:%s:%s',
                ConversationKind::Internal->value,
                $staff->id,
                $counterpart->id,
            ),
        ));
    }
}
