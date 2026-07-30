<?php

use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Chat\Models\Participant;
use App\Enums\ChatContextType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

test('a conversation holds participants and messages', function () {
    $conversation = Conversation::factory()
        ->has(Participant::factory()->count(2), 'participants')
        ->has(Message::factory()->count(3), 'messages')
        ->create();

    expect($conversation->participants)->toHaveCount(2)
        ->and($conversation->messages)->toHaveCount(3);
});

test('membership recognises an active participant', function () {
    $id = (string) Str::ulid();
    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($id), 'participants')
        ->create();

    expect($conversation->hasParticipant($id))->toBeTrue()
        ->and($conversation->hasParticipant((string) Str::ulid()))->toBeFalse();
});

/**
 * Leaving revokes access but preserves the row and the messages, so history
 * stays intact for any future response-speed metric.
 */
test('someone who has left is no longer a member', function () {
    $id = (string) Str::ulid();
    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($id)->departed(), 'participants')
        ->create();

    expect($conversation->hasParticipant($id))->toBeFalse()
        ->and($conversation->participants)->toHaveCount(1);
});

test('the authorizer admits members and refuses everyone else', function () {
    $member = (string) Str::ulid();
    $stranger = (string) Str::ulid();

    $conversation = Conversation::factory()
        ->has(Participant::factory()->for_($member), 'participants')
        ->create();

    $authorizer = app(ChatAuthorizer::class);

    expect($authorizer->canAccess($member, $conversation))->toBeTrue()
        ->and($authorizer->canAccess($stranger, $conversation))->toBeFalse();
});

/**
 * unique_key is the module's generic at-most-one slot. It replaces a partial
 * index on context_type, which would have baked a domain value into the
 * module's schema.
 */
test('unique_key permits only one conversation per key', function () {
    $key = 'application:'.Str::ulid();

    Conversation::factory()->create(['unique_key' => $key]);

    expect(fn () => Conversation::factory()->create(['unique_key' => $key]))
        ->toThrow(QueryException::class);
});

test('a null unique_key permits many conversations for the same context', function () {
    $jobId = (string) Str::ulid();

    Conversation::factory()->count(3)
        ->boundTo(ChatContextType::Job->value, $jobId)
        ->create();

    expect(Conversation::query()->where('context_id', $jobId)->count())->toBe(3);
});

test('a message may be a system message with no author', function () {
    $message = Message::factory()->system('Status changed to shortlisted.')->create();

    expect($message->participant_id)->toBeNull()
        ->and($message->isSystem())->toBeTrue();
});

/**
 * Soft delete, so a removed message stops rendering without vanishing from the
 * history a metric would be computed over.
 */
test('a deleted message is retained for history', function () {
    $message = Message::factory()->create();
    $message->delete();

    expect(Message::query()->count())->toBe(0)
        ->and(Message::withTrashed()->count())->toBe(1);
});

test('messages sort chronologically by their ulid', function () {
    $conversation = Conversation::factory()->create();

    $first = Message::factory()->for($conversation)->create();
    $second = Message::factory()->for($conversation)->create();

    expect($conversation->messages()->orderBy('id')->pluck('id')->all())
        ->toBe([$first->id, $second->id]);
});
