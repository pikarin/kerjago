<?php

namespace App\Chat\Contracts;

use App\Chat\Data\ContextData;

/**
 * Implemented by the host application. The module knows a conversation may be
 * bound to something, but not what a job or an application is.
 */
interface ContextResolver
{
    /**
     * Resolve labels for a set of context ids of one type.
     *
     * Batch-shaped for the same reason as ParticipantResolver. Implementations
     * MUST return an entry for every requested id, falling back to
     * ContextData::placeholder() for ids they cannot resolve, and MUST tolerate
     * a `$type` they do not recognise rather than throwing.
     *
     * @param  list<string>  $ids
     * @return array<string, ContextData> keyed by context id
     */
    public function resolve(string $type, array $ids): array;
}
