<?php

namespace App\Chat\Actions;

use App\Chat\Models\Conversation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ListConversations
{
    /**
     * A participant's inbox, newest activity first.
     *
     * Returns a paginator of models. Formatting and identity resolution are the
     * host's job (.ai/guidelines/laravel-actions), which is what lets a web
     * controller and a future API controller share this untouched.
     *
     * unread_count is a correlated subquery rather than a per-row count, so the
     * inbox costs a fixed number of queries however many conversations it lists.
     *
     * @return LengthAwarePaginator<int, Conversation>
     */
    public function handle(string $participantId, int $perPage = 20): LengthAwarePaginator
    {
        return Conversation::query()
            ->whereHas(
                'participants',
                fn (Builder $query) => $query
                    ->where('participant_id', $participantId)
                    ->whereNull('left_at'),
            )
            ->with('participants')
            ->withCount([
                'messages as unread_count' => fn (Builder $query) => $query
                    // Own messages are never unread. System messages are —
                    // a status change is news. Their participant_id is null,
                    // and `null != x` is null in SQL, so they need the explicit
                    // branch or they would be silently excluded.
                    ->where(fn (Builder $author) => $author
                        ->whereNull('participant_id')
                        ->orWhere('participant_id', '!=', $participantId),
                    )
                    // coalesce to '' matters: a participant who has never read
                    // has a null marker, and `id > null` is null, which would
                    // report zero unread instead of everything unread. Empty
                    // string sorts before every ULID.
                    ->whereRaw(
                        "chat_messages.id > coalesce((
                            select last_read_message_id
                            from chat_participants
                            where chat_participants.conversation_id = chat_conversations.id
                              and chat_participants.participant_id = ?
                            limit 1
                        ), '')",
                        [$participantId],
                    ),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
