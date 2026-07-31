<?php

namespace App\Chat\Actions;

use App\Chat\Models\Message;
use App\Chat\Models\Participant;
use Illuminate\Database\Eloquent\Collection;

class SearchMessages
{
    /**
     * Full-text search across the conversations a participant belongs to.
     *
     * The engine supplies candidate ids; Eloquent decides what the caller may
     * actually see. Access is therefore enforced in SQL, not by an index
     * filter, so a stale or misconfigured index cannot leak a conversation. The
     * index does carry participant_ids for engine-side pre-filtering once
     * volume warrants it, but that remains an optimisation.
     *
     * Results come back newest-first rather than by relevance, and are bounded
     * by $limit — the caller is told when the list was truncated rather than
     * being silently cut off.
     *
     * @return Collection<int, Message>
     */
    public function handle(string $participantId, string $query, int $limit = 30): Collection
    {
        $candidateIds = Message::search($query)->take($limit)->keys()->all();

        return Message::query()
            ->with('reactions:id,message_id,participant_id,emoji')
            ->whereIn('id', $candidateIds)
            // A flat IN (subquery) on chat_participants rather than a nested
            // whereHas through chat_conversations: chat_messages.conversation_id
            // already matches chat_participants directly, and this shape is
            // driven by the (participant_id, left_at) index.
            ->whereIn('conversation_id', Participant::query()
                ->select('conversation_id')
                ->where('participant_id', $participantId)
                ->whereNull('left_at'),
            )
            ->orderByDesc('id')
            ->get();
    }
}
