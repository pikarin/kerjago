<?php

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
