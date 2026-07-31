<?php

use App\Chat\Actions\ListConversations;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Chat\Models\Participant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

/**
 * The socket-side twin of the HTTP authorization test, and the one most easily
 * forgotten: a chat app that authorizes the page but not the subscription leaks
 * every message in real time.
 */
function subscribeTo(Conversation $conversation): TestResponse
{
    return test()->postJson('/broadcasting/auth', [
        'channel_name' => 'presence-chat.conversations.'.$conversation->id,
        'socket_id' => '1234.5678',
    ]);
}

test('a member may subscribe to the conversation channel', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($user->id), 'participants')
        ->create();

    $this->actingAs($user);

    subscribeTo($conversation)->assertOk();
});

/**
 * An employer is shown as their company everywhere in chat. The presence
 * payload must not carry the personal account name behind that, and nothing on
 * the client reads it.
 */
test('the presence payload does not leak the account name', function () {
    $user = User::factory()->create(['name' => 'Personal Account Name']);
    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($user->id), 'participants')
        ->create();

    $response = $this->actingAs($user)->postJson('/broadcasting/auth', [
        'channel_name' => 'presence-chat.conversations.'.$conversation->id,
        'socket_id' => '1234.5678',
    ])->assertOk();

    expect($response->getContent())->not->toContain('Personal Account Name');
});

test('a non-member may not subscribe to the conversation channel', function () {
    $stranger = User::factory()->create();
    $conversation = Conversation::factory()
        ->has(Participant::factory(), 'participants')
        ->create();

    $this->actingAs($stranger);

    subscribeTo($conversation)->assertForbidden();
});

test('someone who has left may no longer subscribe', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($user->id)->departed(), 'participants')
        ->create();

    $this->actingAs($user);

    subscribeTo($conversation)->assertForbidden();
});

test('a guest may not subscribe', function () {
    $conversation = Conversation::factory()
        ->has(Participant::factory(), 'participants')
        ->create();

    subscribeTo($conversation)->assertForbidden();
});

test('the inbox lists only conversations the participant is in', function () {
    $me = (string) Str::ulid();

    $mine = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => now()]);

    Conversation::factory()
        ->has(Participant::factory(), 'participants')
        ->create(['last_message_at' => now()]);

    $inbox = app(ListConversations::class)->handle($me);

    expect($inbox->total())->toBe(1)
        ->and($inbox->items()[0]->id)->toBe($mine->id);
});

test('the inbox orders by most recent activity', function () {
    $me = (string) Str::ulid();

    $older = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => now()->subDay()]);

    $newer = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => now()]);

    $ids = collect(app(ListConversations::class)->handle($me)->items())->pluck('id')->all();

    expect($ids)->toBe([$newer->id, $older->id]);
});

/**
 * Postgres orders DESC as NULLS FIRST. Cold outreach and internal threads start
 * with last_message_at null, so without an explicit NULLS LAST a conversation
 * nobody has written in pinned above every live one.
 */
test('a conversation with no activity sorts below one with activity', function () {
    $me = (string) Str::ulid();

    $silent = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => null]);

    $active = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => now()->subWeek()]);

    $ids = collect(app(ListConversations::class)->handle($me)->items())->pluck('id')->all();

    expect($ids)->toBe([$active->id, $silent->id]);
});

/**
 * A participant who has never read anything has a null marker. `id > null` is
 * null in SQL, so without the coalesce this reported zero unread instead of
 * everything unread.
 */
test('a never-read conversation counts every incoming message as unread', function () {
    $me = (string) Str::ulid();
    $them = (string) Str::ulid();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => now()]);

    Message::factory()->count(3)->for($conversation)->from($them)->create();

    expect(app(ListConversations::class)->handle($me)->items()[0]->unread_count)->toBe(3);
});

test('own messages are never unread but system messages are', function () {
    $me = (string) Str::ulid();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($me), 'participants')
        ->create(['last_message_at' => now()]);

    Message::factory()->count(2)->for($conversation)->from($me)->create();
    Message::factory()->for($conversation)->system()->create();

    expect(app(ListConversations::class)->handle($me)->items()[0]->unread_count)->toBe(1);
});

test('the inbox costs a fixed number of queries regardless of size', function () {
    $me = (string) Str::ulid();

    $count = function (int $conversations) use ($me): int {
        Conversation::query()->delete();

        Conversation::factory()->count($conversations)
            ->has(Participant::factory()->for_($me), 'participants')
            ->create(['last_message_at' => now()]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ListConversations::class)->handle($me);
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    expect($count(15))->toBe($count(2));
});
