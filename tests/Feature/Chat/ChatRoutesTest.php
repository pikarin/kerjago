<?php

use App\Chat\Data\MessagePayload;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Chat\Models\Participant;
use App\Enums\ChatContextType;
use App\Http\Resources\Chat\MessageResource;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @return array{0: Conversation, 1: User}
 */
function conversationForUser(?User $user = null): array
{
    $user ??= User::factory()->create();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($user->id), 'participants')
        ->has(Participant::factory(), 'participants')
        ->create(['last_message_at' => now()]);

    return [$conversation, $user];
}

test('the inbox lists the viewer conversations', function () {
    [$conversation, $user] = conversationForUser();

    $this->actingAs($user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/Index')
            ->where('conversations.data.0.id', $conversation->id)
            ->where('conversation', null),
        );
});

test('guests are redirected away from chat', function () {
    $this->get(route('chat.index'))->assertRedirect(route('login'));
});

test('a participant can open a conversation', function () {
    [$conversation, $user] = conversationForUser();
    Message::factory()->for($conversation)->create();

    $this->actingAs($user)
        ->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('chat/Index')
            ->where('conversation.id', $conversation->id)
            ->has('messages.data', 1),
        );
});

/**
 * The inbox UI labels messages by looking their participant_id up in the
 * conversation's participant list, and identifies the viewer from the shared
 * auth prop. Both are pinned here because trimming either would break rendering
 * at runtime without failing any other test.
 */
test('the chat page carries the viewer identity the UI needs', function () {
    [$conversation, $user] = conversationForUser();

    $this->actingAs($user)
        ->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.id', $user->id)
            ->has('conversation.participants', 2)
            ->where(
                'conversation.participants',
                fn ($participants) => collect($participants)
                    ->where('is_viewer', true)
                    ->count() === 1,
            ),
        );
});

test('a non-participant cannot open a conversation', function () {
    [$conversation] = conversationForUser();

    $this->actingAs(User::factory()->create())
        ->get(route('chat.show', $conversation))
        ->assertForbidden();
});

test('a non-participant cannot post a message', function () {
    [$conversation] = conversationForUser();

    $this->actingAs(User::factory()->create())
        ->post(route('chat.messages.store', $conversation), ['body' => 'Let me in'])
        ->assertForbidden();

    expect(Message::query()->count())->toBe(0);
});

test('a participant can post a message', function () {
    [$conversation, $user] = conversationForUser();

    $this->actingAs($user)
        ->post(route('chat.messages.store', $conversation), ['body' => 'Morning'])
        ->assertRedirect();

    expect(Message::query()->firstOrFail()->body)->toBe('Morning');
});

test('a message body is required and bounded', function () {
    [$conversation, $user] = conversationForUser();

    $this->actingAs($user)
        ->post(route('chat.messages.store', $conversation), ['body' => ''])
        ->assertSessionHasErrors('body');

    $this->actingAs($user)
        ->post(route('chat.messages.store', $conversation), ['body' => str_repeat('a', 5001)])
        ->assertSessionHasErrors('body');
});

test('a participant can acknowledge a read explicitly', function () {
    [$conversation, $user] = conversationForUser();
    Message::factory()->for($conversation)->create();

    $this->actingAs($user)
        ->post(route('chat.read.store', $conversation))
        ->assertRedirect();

    expect($conversation->participants()->where('participant_id', $user->id)->value('last_read_at'))
        ->not->toBeNull();
});

test('a non-participant cannot acknowledge a read', function () {
    [$conversation] = conversationForUser();

    $this->actingAs(User::factory()->create())
        ->post(route('chat.read.store', $conversation))
        ->assertForbidden();
});

/**
 * parent_message_id carries a real self-referential foreign key, so an id that
 * merely looks like a ULID would pass a `ulid` rule and then fail the
 * constraint as a 500. Scoping also blocks a thread parent from another
 * conversation.
 */
test('a thread parent must exist inside the same conversation', function () {
    [$mine, $user] = conversationForUser();
    [$theirs] = conversationForUser();

    $foreign = Message::factory()->for($theirs)->create();

    $this->actingAs($user)
        ->post(route('chat.messages.store', $mine), [
            'body' => 'Replying across a boundary',
            'parent_message_id' => $foreign->id,
        ])
        ->assertSessionHasErrors('parent_message_id');

    $this->actingAs($user)
        ->post(route('chat.messages.store', $mine), [
            'body' => 'Replying to nothing',
            'parent_message_id' => (string) Str::ulid(),
        ])
        ->assertSessionHasErrors('parent_message_id');

    expect(Message::query()->where('conversation_id', $mine->id)->count())->toBe(0);
});

test('a thread parent inside the same conversation is accepted', function () {
    [$conversation, $user] = conversationForUser();
    $parent = Message::factory()->for($conversation)->create();

    $this->actingAs($user)
        ->post(route('chat.messages.store', $conversation), [
            'body' => 'Threaded reply',
            'parent_message_id' => $parent->id,
        ])
        ->assertRedirect();

    expect(Message::query()->where('parent_message_id', $parent->id)->count())->toBe(1);
});

test('the inbox search query is bounded', function () {
    [, $user] = conversationForUser();

    $this->actingAs($user)
        ->get(route('chat.index', ['q' => str_repeat('a', 256)]))
        ->assertSessionHasErrors('q');
});

test('opening a conversation marks it read', function () {
    [$conversation, $user] = conversationForUser();
    Message::factory()->for($conversation)->create();

    $this->actingAs($user)->get(route('chat.show', $conversation))->assertOk();

    $participant = $conversation->participants()
        ->where('participant_id', $user->id)
        ->firstOrFail();

    expect($participant->last_read_at)->not->toBeNull();
});

/**
 * Reacting is scoped to the conversation, so a real message id from a
 * conversation the caller cannot see must be rejected rather than reacted to.
 */
test('a reaction cannot target a message from another conversation', function () {
    [$mine, $user] = conversationForUser();
    [$theirs] = conversationForUser();

    $foreignMessage = Message::factory()->for($theirs)->create();

    $this->actingAs($user)
        ->post(route('chat.reactions.store', $mine), [
            'message_id' => $foreignMessage->id,
            'emoji' => '👍',
        ])
        ->assertSessionHasErrors('message_id');

    expect($foreignMessage->reactions()->count())->toBe(0);
});

/**
 * The exists rule excludes soft-deleted rows, so this is a validation error
 * rather than a 404 from the controller's findOrFail.
 */
test('a reaction cannot target a deleted message', function () {
    [$conversation, $user] = conversationForUser();
    $message = Message::factory()->for($conversation)->create();
    $message->delete();

    $this->actingAs($user)
        ->post(route('chat.reactions.store', $conversation), [
            'message_id' => $message->id,
            'emoji' => '👍',
        ])
        ->assertSessionHasErrors('message_id');
});

test('a participant can react to a message in their conversation', function () {
    [$conversation, $user] = conversationForUser();
    $message = Message::factory()->for($conversation)->create();

    $this->actingAs($user)
        ->post(route('chat.reactions.store', $conversation), [
            'message_id' => $message->id,
            'emoji' => '🎉',
        ])
        ->assertRedirect();

    expect($message->reactions()->count())->toBe(1);
});

/**
 * The HTTP payload and the socket payload are built from the same definition.
 * If they diverge, a message renders one way live and another after a refresh.
 */
test('the message resource matches the broadcast payload', function () {
    [$conversation] = conversationForUser();
    $message = Message::factory()->for($conversation)->create();

    $resource = (new MessageResource($message))->resolve();
    $payload = MessagePayload::fromMessage($message)->toArray();

    foreach ($payload as $key => $value) {
        expect($resource[$key])->toBe($value);
    }
});

test('a deleted job renders as unavailable context rather than failing', function () {
    $user = User::factory()->create();
    $job = Job::factory()->create();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($user->id), 'participants')
        ->boundTo(ChatContextType::Job->value, $job->id)
        ->create(['last_message_at' => now()]);

    $job->delete();

    $this->actingAs($user)
        ->get(route('chat.show', $conversation))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('conversation.context.unavailable', true));
});

/**
 * Identity must be resolved once per request, not once per message. This is
 * invisible in a monolith and becomes a network round-trip per message once chat
 * is extracted, so the guard is that query count does not grow with history.
 */
test('opening a conversation costs the same queries regardless of message count', function () {
    $countFor = function (int $messages): int {
        DB::table('chat_messages')->delete();
        DB::table('chat_participants')->delete();
        DB::table('chat_conversations')->delete();

        [$conversation, $user] = conversationForUser();
        Message::factory()->count($messages)->for($conversation)->create();

        $this->actingAs($user);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(route('chat.show', $conversation))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queries;
    };

    expect($countFor(20))->toBe($countFor(2));
});

test('an employer can start cold outreach from talent search', function () {
    $employer = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    $this->actingAs($employer->user)
        ->post(route('employer.talent.chat', $target))
        ->assertRedirect();

    expect(Conversation::query()->count())->toBe(1);
});

test('cold outreach from talent search does not duplicate on a second click', function () {
    $employer = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    $this->actingAs($employer->user)->post(route('employer.talent.chat', $target));
    $this->actingAs($employer->user)->post(route('employer.talent.chat', $target));

    expect(Conversation::query()->count())->toBe(1);
});

test('a jobseeker cannot start cold outreach', function () {
    $jobseeker = JobseekerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    $this->actingAs($jobseeker->user)
        ->post(route('employer.talent.chat', $target))
        ->assertForbidden();
});
