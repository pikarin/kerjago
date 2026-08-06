<?php

namespace App\Chat\Data;

/**
 * Everything needed to open a conversation.
 *
 * A DTO rather than seven positional arguments, per .ai/guidelines
 * /laravel-actions. `kind`, `contextType` and `contextId` are opaque to the
 * module; the host supplies values from its own enums.
 */
readonly class NewConversation
{
    /**
     * @param  list<string>  $participantIds
     * @param  list<string>  $withheldParticipantIds  Participants created with
     *                                                access already revoked
     *                                                (`left_at` set), for a host
     *                                                that knows who belongs in a
     *                                                thread before it knows they
     *                                                may read it. Restored later
     *                                                through RestoreParticipant.
     * @param  array<string, mixed>|null  $meta
     * @param  string|null  $uniqueKey  Set to enforce at-most-one conversation
     *                                  for this key (the host composes something
     *                                  like "application:{ulid}"). Null means the
     *                                  same context may hold many conversations,
     *                                  as a job does — one per candidate.
     */
    public function __construct(
        public string $kind,
        public string $createdByParticipantId,
        public array $participantIds,
        public array $withheldParticipantIds = [],
        public ?string $contextType = null,
        public ?string $contextId = null,
        public ?string $uniqueKey = null,
        public ?array $meta = null,
    ) {}
}
