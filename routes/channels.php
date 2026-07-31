<?php

use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| The scaffolded version of this callback compared `(int)` casts of the two
| IDs. This application uses ULID primary keys (ADR 0002), and `(int)` of any
| ULID is 1 — every ULID begins with a zero-padded timestamp. That comparison
| was therefore `1 === 1` for every pair of users, which authorized any
| authenticated user on any other user's private channel. Compare as strings.
|
*/

Broadcast::channel('App.Models.User.{id}', function (User $user, string $id): bool {
    return $user->id === $id;
});

/*
 * Chat conversations.
 *
 * A presence channel, so returning the array is what enables here(), joining()
 * and leaving() — and typing indicators, which travel client-to-client over
 * whisper() and never touch the server.
 *
 * Authorization delegates to ChatAuthorizer, the same contract the HTTP layer
 * uses. Routing both through one method is what stops the page and the socket
 * from drifting apart, which is the usual way a chat app ends up authorizing
 * one but not the other.
 *
 * The payload carries the id and nothing else. It must NOT include $user->name:
 * that is the personal account name, whereas an employer is shown throughout
 * chat as their company (see EloquentParticipantResolver::displayName). Sending
 * it here would hand every other participant an identity the rest of the
 * feature deliberately does not show. The client resolves names from the
 * conversation's participant list instead.
 *
 * @return array{id: string}|null
 */
Broadcast::channel('chat.conversations.{conversation}', function (User $user, Conversation $conversation): ?array {
    if (! app(ChatAuthorizer::class)->canAccess($user->id, $conversation)) {
        return null;
    }

    return ['id' => $user->id];
});
