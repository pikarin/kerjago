<?php

namespace App\Chat\Actions;

use App\Chat\Events\MessageRead;
use App\Chat\Models\Conversation;
use App\Chat\Models\Participant;

class MarkConversationRead
{
    /**
     * Advance a participant's read marker to the newest message.
     *
     * `last_read_at` is the field any future response-speed metric depends on:
     * a read receipt carrying a timestamp. It cannot be reconstructed later, so
     * it is recorded from the first day even though no metric consumes it yet.
     *
     * Returns null when the caller is not a participant — reading is not a
     * mutation worth throwing over.
     */
    public function handle(Conversation $conversation, string $participantId): ?Participant
    {
        $participant = $conversation->participants()
            ->where('participant_id', $participantId)
            ->whereNull('left_at')
            ->first();

        if ($participant === null) {
            return null;
        }

        $latestMessageId = $conversation->messages()
            ->orderByDesc('id')
            ->value('id');

        if ($latestMessageId === null) {
            return $participant;
        }

        // Never move the marker backwards. ULIDs sort chronologically, so a
        // late request from a stale tab cannot un-read newer messages.
        if ($participant->last_read_message_id !== null
            && $participant->last_read_message_id >= $latestMessageId) {
            return $participant;
        }

        $participant->forceFill([
            'last_read_message_id' => $latestMessageId,
            'last_read_at' => now(),
        ])->save();

        MessageRead::dispatch($participant);

        return $participant;
    }
}
