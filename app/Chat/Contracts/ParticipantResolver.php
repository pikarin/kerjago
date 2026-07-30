<?php

namespace App\Chat\Contracts;

use App\Chat\Data\ParticipantData;

/**
 * Implemented by the host application. The module defines this interface and
 * depends on nothing outside itself — that inversion is what lets app/Chat/ be
 * lifted into its own service later.
 */
interface ParticipantResolver
{
    /**
     * Resolve display identities for a set of participant ids.
     *
     * Deliberately batch-shaped. A per-id resolver is an N+1 inside the
     * monolith and becomes one network round-trip per message once chat is
     * extracted, so the signature takes a set and returns a map.
     *
     * Implementations MUST return an entry for every requested id, falling back
     * to ParticipantData::placeholder() for ids they cannot resolve.
     *
     * @param  list<string>  $ids
     * @return array<string, ParticipantData> keyed by participant id
     */
    public function resolve(array $ids): array;
}
