<?php

use App\Chat\Actions\SearchMessages;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Chat\Models\Participant;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * phpunit.xml sets SCOUT_DRIVER=null, whose engine returns nothing for every
 * query — every assertion below would pass without testing anything. The
 * collection driver searches the database in-process, so these run for real
 * without needing Typesense.
 */
beforeEach(function () {
    config(['scout.driver' => 'collection']);
});

/**
 * @return array{0: Conversation, 1: string}
 */
function searchableConversation(?string $participantId = null): array
{
    $participantId ??= (string) Str::ulid();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($participantId), 'participants')
        ->create();

    return [$conversation, $participantId];
}

test('a participant finds their own messages', function () {
    [$conversation, $me] = searchableConversation();

    Message::factory()->for($conversation)->from($me)->create([
        'body' => 'Can we schedule the interview for Tuesday',
    ]);
    Message::factory()->for($conversation)->from($me)->create([
        'body' => 'Sending over my portfolio',
    ]);

    $results = app(SearchMessages::class)->handle($me, 'interview');

    expect($results->count())->toBe(1)
        ->and($results->first()?->body)->toContain('interview');
});

/**
 * The load-bearing test for this commit. Scoping is enforced in SQL rather than
 * by an engine filter precisely so this cannot depend on index configuration.
 */
test('search never returns a message from a conversation the user is not in', function () {
    [, $me] = searchableConversation();
    [$theirs] = searchableConversation();

    Message::factory()->for($theirs)->create([
        'body' => 'Confidential salary discussion',
    ]);

    // Establishes the leak is reachable: the engine does match this message,
    // so the assertion below is testing the SQL scope rather than an empty
    // index.
    expect(Message::search('salary')->get())->toHaveCount(1);

    $results = app(SearchMessages::class)->handle($me, 'salary');

    expect($results->count())->toBe(0);
});

test('someone who has left a conversation can no longer search it', function () {
    $me = (string) Str::ulid();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($me)->departed(), 'participants')
        ->create();

    Message::factory()->for($conversation)->create(['body' => 'Budget approved']);

    expect(app(SearchMessages::class)->handle($me, 'Budget')->count())->toBe(0);
});

test('a soft-deleted message drops out of the index', function () {
    [$conversation, $me] = searchableConversation();

    $message = Message::factory()->for($conversation)->from($me)->create([
        'body' => 'Retracted offer details',
    ]);

    expect(app(SearchMessages::class)->handle($me, 'Retracted')->count())->toBe(1);

    $message->delete();

    expect(app(SearchMessages::class)->handle($me, 'Retracted')->count())->toBe(0)
        // Still present for history, which any future response-speed metric
        // is computed over.
        ->and(Message::withTrashed()->count())->toBe(1);
});

test('system messages are searchable', function () {
    [$conversation, $me] = searchableConversation();

    Message::factory()->for($conversation)->system('Application status changed to shortlisted.')->create();

    expect(app(SearchMessages::class)->handle($me, 'shortlisted')->count())->toBe(1);
});

test('the inbox returns search results for a query and none without one', function () {
    $user = User::factory()->create();
    [$conversation] = searchableConversation($user->id);

    Message::factory()->for($conversation)->from($user->id)->create([
        'body' => 'Portfolio link attached',
    ]);

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('searchResults', null));

    $this->actingAs($user)
        ->get(route('chat.index', ['q' => 'Portfolio']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('searchQuery', 'Portfolio')
            ->has('searchResults.data', 1),
        );
});

test('the inbox search endpoint does not leak another conversation', function () {
    $user = User::factory()->create();
    searchableConversation($user->id);
    [$theirs] = searchableConversation();

    Message::factory()->for($theirs)->create(['body' => 'Private negotiation notes']);

    $this->actingAs($user)
        ->get(route('chat.index', ['q' => 'negotiation']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('searchResults.data', 0));
});

test('the searchable payload carries the conversation participants', function () {
    [$conversation, $me] = searchableConversation();

    $message = Message::factory()->for($conversation)->from($me)->create();

    $searchable = $message->toSearchableArray();

    expect($searchable['participant_ids'])->toContain($me)
        ->and($searchable['conversation_id'])->toBe($conversation->id)
        ->and($searchable)->toHaveKeys(['id', 'body', 'created_at']);
});
