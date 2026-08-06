<?php

namespace App\Chat\Actions;

use App\Chat\Models\Participant;

/**
 * Give a participant back the access `left_at` revoked, in named conversations.
 *
 * The mirror of leaving. Read state, message history and the participant row
 * itself are untouched, so someone restored sees the whole thread — including
 * what was said while they were out.
 *
 * Conversations are named explicitly rather than "every thread this person is
 * in": a host restoring access for one reason must not silently undo an unrelated
 * departure.
 */
class RestoreParticipant
{
    /**
     * @param  list<string>  $conversationIds
     * @return int the number of participant rows restored
     */
    public function handle(string $participantId, array $conversationIds): int
    {
        if ($conversationIds === []) {
            return 0;
        }

        return Participant::query()
            ->where('participant_id', $participantId)
            ->whereIn('conversation_id', $conversationIds)
            ->whereNotNull('left_at')
            ->update(['left_at' => null]);
    }
}
