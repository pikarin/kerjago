<?php

namespace App\Chat\Data;

/**
 * A participant's display identity, resolved by the host application.
 *
 * The chat module stores only opaque participant ids, so it never holds a copy
 * of a name or an avatar and can never show a stale one.
 */
readonly class ParticipantData
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $avatarUrl = null,
        public ?string $profileUrl = null,
        public bool $isPlaceholder = false,
    ) {}

    /**
     * Stand-in for an id the host can no longer resolve.
     *
     * Chat holds no foreign key to the host's user table, so a participant id
     * can outlive the account it referred to. That is expected, not an error:
     * the messages must still render.
     */
    public static function placeholder(string $id): self
    {
        return new self(
            id: $id,
            name: __('Deleted user'),
            isPlaceholder: true,
        );
    }
}
