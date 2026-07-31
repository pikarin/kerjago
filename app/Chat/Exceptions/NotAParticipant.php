<?php

namespace App\Chat\Exceptions;

use RuntimeException;

/**
 * Thrown when someone outside a conversation tries to write to it.
 *
 * Authorization proper belongs to the host's HTTP layer; this is the module
 * refusing to record data that would be corrupt regardless of how it arrived.
 */
class NotAParticipant extends RuntimeException
{
    public static function for(string $participantId, string $conversationId): self
    {
        return new self("Participant [{$participantId}] is not in conversation [{$conversationId}].");
    }
}
