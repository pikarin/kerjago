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
 * @return array{id: string, name: string}|null
 */
Broadcast::channel('chat.conversations.{conversation}', function (User $user, Conversation $conversation): ?array {
    if (! app(ChatAuthorizer::class)->canAccess($user->id, $conversation)) {
        return null;
    }

    return ['id' => $user->id, 'name' => $user->name];
});
