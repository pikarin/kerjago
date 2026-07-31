<?php

namespace App\Chat\Data;

/**
 * What a conversation is bound to, resolved by the host application.
 *
 * `type` is an opaque string as far as the module is concerned. Kerjago passes
 * values from App\Enums\ChatContextType; the module never inspects them.
 */
readonly class ContextData
{
    public function __construct(
        public string $type,
        public string $id,
        public string $label,
        public ?string $url = null,
        public bool $isPlaceholder = false,
    ) {}

    /**
     * Stand-in for a context the host can no longer resolve — a deleted job,
     * for instance. There is no foreign key, so this is a normal state and
     * the conversation must still open.
     */
    public static function placeholder(string $type, string $id): self
    {
        return new self(
            type: $type,
            id: $id,
            label: __('No longer available'),
            isPlaceholder: true,
        );
    }
}
