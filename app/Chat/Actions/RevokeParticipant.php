<?php

namespace App\Chat\Actions;

use App\Chat\Models\Participant;

/**
 * Revoke a participant's access to named conversations without erasing them.
 *
 * Same mechanism as leaving voluntarily: the row and every message stay, so a
 * later RestoreParticipant returns the full history rather than a gap.
 */
class RevokeParticipant
{
    /**
     * @param  list<string>  $conversationIds
     * @return int the number of participant rows revoked
     */
    public function handle(string $participantId, array $conversationIds): int
    {
        if ($conversationIds === []) {
            return 0;
        }

        return Participant::query()
            ->where('participant_id', $participantId)
            ->whereIn('conversation_id', $conversationIds)
            ->whereNull('left_at')
            ->update(['left_at' => now()]);
    }
}
