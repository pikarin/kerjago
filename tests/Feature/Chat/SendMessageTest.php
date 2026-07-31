<?php

use App\Chat\Actions\MarkConversationRead;
use App\Chat\Actions\PostSystemMessage;
use App\Chat\Actions\SendMessage;
use App\Chat\Actions\ToggleReaction;
use App\Chat\Data\MessagePayload;
use App\Chat\Events\MessageRead;
use App\Chat\Events\MessageSent;
use App\Chat\Exceptions\NotAParticipant;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Chat\Models\Participant;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

/**
 * @return array{0: Conversation, 1: string}
 */
function conversationWithMember(): array
{
    $member = (string) Str::ulid();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($member), 'participants')
        ->create();

    return [$conversation, $member];
}

test('sending a message records it and stamps the conversation', function () {
    [$conversation, $member] = conversationWithMember();

    $message = app(SendMessage::class)->handle($conversation, $member, 'Are you available next week?');

    expect($message->body)->toBe('Are you available next week?')
        ->and($message->participant_id)->toBe($member)
        ->and($conversation->fresh()->last_message_at)->not->toBeNull();
});

test('sending a message announces it', function () {
    [$conversation, $member] = conversationWithMember();

    app(SendMessage::class)->handle($conversation, $member, 'Hello');

    Event::assertDispatched(MessageSent::class);
});

/**
 * Data integrity, distinct from authorization: a message from outside the
 * conversation would be corrupt however it arrived.
 */
test('a non-participant cannot send a message', function () {
    [$conversation] = conversationWithMember();

    expect(fn () => app(SendMessage::class)->handle($conversation, (string) Str::ulid(), 'Let me in'))
        ->toThrow(NotAParticipant::class);

    expect(Message::query()->count())->toBe(0);
});

test('someone who has left cannot send a message', function () {
    $departed = (string) Str::ulid();
    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($departed)->departed(), 'participants')
        ->create();

    expect(fn () => app(SendMessage::class)->handle($conversation, $departed, 'Still here?'))
        ->toThrow(NotAParticipant::class);
});

test('a system message needs no author and no membership', function () {
    [$conversation] = conversationWithMember();

    $message = app(PostSystemMessage::class)->handle($conversation, 'Status changed to shortlisted.');

    expect($message->participant_id)->toBeNull()
        ->and($message->isSystem())->toBeTrue()
        ->and($conversation->fresh()->last_message_at)->not->toBeNull();
});

test('marking read advances the marker and stamps a timestamp', function () {
    [$conversation, $member] = conversationWithMember();

    $message = Message::factory()->for($conversation)->create();

    $participant = app(MarkConversationRead::class)->handle($conversation, $member);

    expect($participant?->last_read_message_id)->toBe($message->id)
        ->and($participant?->last_read_at)->not->toBeNull();

    Event::assertDispatched(MessageRead::class);
});

/**
 * ULIDs sort chronologically, so a late request from a stale tab must not
 * un-read newer messages.
 */
test('the read marker never moves backwards', function () {
    [$conversation, $member] = conversationWithMember();

    Message::factory()->for($conversation)->create();
    app(MarkConversationRead::class)->handle($conversation, $member);

    $advanced = $conversation->participants()->first()?->last_read_message_id;

    // Replay against an unchanged conversation.
    app(MarkConversationRead::class)->handle($conversation->fresh(), $member);

    expect($conversation->participants()->first()?->last_read_message_id)->toBe($advanced);
});

test('marking read returns null for a non-participant', function () {
    [$conversation] = conversationWithMember();

    expect(app(MarkConversationRead::class)->handle($conversation, (string) Str::ulid()))->toBeNull();
});

test('a reaction toggles off when applied twice', function () {
    [$conversation, $member] = conversationWithMember();
    $message = Message::factory()->for($conversation)->create();

    expect(app(ToggleReaction::class)->handle($message, $member, '👍'))->toBeTrue()
        ->and($message->reactions()->count())->toBe(1)
        ->and(app(ToggleReaction::class)->handle($message, $member, '👍'))->toBeFalse()
        ->and($message->reactions()->count())->toBe(0);
});

/**
 * The socket payload and the HTTP payload are built from one definition, so a
 * message cannot render one way live and differently after a refresh.
 */
test('the broadcast payload matches the shared message shape', function () {
    [$conversation, $member] = conversationWithMember();
    $message = Message::factory()->for($conversation)->from($member)->create();

    $broadcast = (new MessageSent($message))->broadcastWith();

    expect($broadcast['message'])->toBe(MessagePayload::fromMessage($message)->toArray())
        ->and($broadcast['message'])->toHaveKeys([
            'id', 'conversation_id', 'participant_id', 'type', 'body',
            'parent_message_id', 'edited_at', 'created_at',
        ]);
});

test('messages broadcast on the conversation presence channel', function () {
    [$conversation, $member] = conversationWithMember();
    $message = Message::factory()->for($conversation)->from($member)->create();

    $channels = (new MessageSent($message))->broadcastOn();

    expect($channels[0]->name)->toBe('presence-chat.conversations.'.$conversation->id);
});
