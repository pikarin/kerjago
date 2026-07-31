<?php

namespace App\Chat\Actions;

use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use Illuminate\Pagination\LengthAwarePaginator;

class GetConversationMessages
{
    /**
     * A page of history, newest first.
     *
     * Newest-first because a chat opens at the bottom and pages backwards; the
     * client reverses for display. ULIDs sort chronologically, so ordering by id
     * needs no timestamp comparison and is covered by the
     * (conversation_id, id) index.
     *
     * @return LengthAwarePaginator<int, Message>
     */
    public function handle(Conversation $conversation, int $perPage = 30): LengthAwarePaginator
    {
        return $conversation->messages()
            ->with('reactions')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
