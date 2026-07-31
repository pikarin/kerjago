<?php

namespace App\Chat\Actions;

use App\Chat\Models\Message;
use App\Chat\Models\MessageReaction;

class ToggleReaction
{
    /**
     * Add a participant's reaction, or remove it if it is already there.
     *
     * Returns true when the reaction now exists, false when it was removed.
     */
    public function handle(Message $message, string $participantId, string $emoji): bool
    {
        $existing = $message->reactions()
            ->where('participant_id', $participantId)
            ->where('emoji', $emoji)
            ->first();

        if ($existing instanceof MessageReaction) {
            $existing->delete();

            return false;
        }

        $message->reactions()->create([
            'participant_id' => $participantId,
            'emoji' => $emoji,
        ]);

        return true;
    }
}
