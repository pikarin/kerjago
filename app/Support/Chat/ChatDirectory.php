<?php

namespace App\Support\Chat;

use App\Chat\Data\ContextData;
use App\Chat\Data\ParticipantData;

/**
 * Everything a request needs to render names and context labels, resolved once.
 *
 * This exists so identity resolution happens per request rather than per
 * message. Without it, a Resource would reach for a resolver per row, which is
 * an N+1 today and a network round-trip per message once chat is extracted.
 */
readonly class ChatDirectory
{
    /**
     * @param  array<string, ParticipantData>  $participants  keyed by participant id
     * @param  array<string, ContextData>  $contexts  keyed "type:id"
     */
    public function __construct(
        public array $participants = [],
        public array $contexts = [],
    ) {}

    /**
     * Always returns something: an id the host could not resolve still has to
     * render, because chat holds no foreign key to guarantee it exists.
     */
    public function participant(string $id): ParticipantData
    {
        return $this->participants[$id] ?? ParticipantData::placeholder($id);
    }

    public function context(?string $type, ?string $id): ?ContextData
    {
        if ($type === null || $id === null) {
            return null;
        }

        return $this->contexts["{$type}:{$id}"] ?? ContextData::placeholder($type, $id);
    }
}
