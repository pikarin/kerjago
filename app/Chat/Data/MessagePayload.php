<?php

namespace App\Chat\Data;

use App\Chat\Models\Message;

/**
 * The single definition of a message's wire shape.
 *
 * Both the HTTP resource and the broadcast event build their payload from here,
 * so the two cannot drift — the usual symptom being a message that renders one
 * way over the socket and differently after a refresh.
 *
 * Deliberately carries `participantId` and no display name. Identity is resolved
 * once per conversation by the host's ParticipantResolver and looked up
 * client-side, which keeps names out of the socket path and avoids resolving an
 * identity per message.
 */
readonly class MessagePayload
{
    public function __construct(
        public string $id,
        public string $conversationId,
        public ?string $participantId,
        public string $type,
        public ?string $body,
        public ?string $parentMessageId,
        public ?string $editedAt,
        public ?string $createdAt,
    ) {}

    public static function fromMessage(Message $message): self
    {
        return new self(
            id: $message->id,
            conversationId: $message->conversation_id,
            participantId: $message->participant_id,
            type: $message->type->value,
            body: $message->body,
            parentMessageId: $message->parent_message_id,
            editedAt: $message->edited_at?->toIso8601String(),
            createdAt: $message->created_at?->toIso8601String(),
        );
    }

    /**
     * Snake_case keys, matching every other payload the Vue side consumes.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversationId,
            'participant_id' => $this->participantId,
            'type' => $this->type,
            'body' => $this->body,
            'parent_message_id' => $this->parentMessageId,
            'edited_at' => $this->editedAt,
            'created_at' => $this->createdAt,
        ];
    }
}
