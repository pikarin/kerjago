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
            ->with('participants:id,conversation_id,participant_id,last_read_at,left_at')
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
                    ->whereRaw($this->newerThanReadMarker(), [$participantId]),
            ])
            // NULLS LAST is not Postgres's default for DESC. Without it a
            // conversation nobody has written in yet — every cold outreach and
            // internal thread starts that way — sorts above every live one,
            // which is the opposite of "newest activity first".
            ->orderByRaw('last_message_at desc nulls last')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Raw predicate matching messages newer than the participant's read marker.
     *
     * The table names are literals rather than `(new Message)->getTable()`,
     * deliberately against the usual guidance. whereRaw() is typed to accept
     * only a `literal-string`, which is an injection guard: interpolating
     * anything — even a trusted model's table name — makes the argument a
     * runtime string and defeats it. Rename-safety is the weaker property, and
     * these three names are pinned by $table on the models in this module.
     *
     * The coalesce to '' is load-bearing: a participant who has never read has
     * a null marker, and `id > null` is null in SQL, which would report zero
     * unread instead of everything unread. An empty string sorts before every
     * ULID.
     *
     * @return literal-string
     */
    private function newerThanReadMarker(): string
    {
        return "chat_messages.id > coalesce((
            select last_read_message_id
            from chat_participants
            where chat_participants.conversation_id = chat_conversations.id
              and chat_participants.participant_id = ?
            limit 1
        ), '')";
    }
}
