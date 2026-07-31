<?php

use App\Chat\Actions\OpenConversation;
use App\Chat\Data\NewConversation;
use App\Chat\Models\Conversation;
use App\Enums\ChatContextType;
use App\Enums\ConversationKind;
use Illuminate\Support\Str;

function openConversation(): OpenConversation
{
    return app(OpenConversation::class);
}

test('opening a conversation records its participants', function () {
    $employer = (string) Str::ulid();
    $jobseeker = (string) Str::ulid();

    $conversation = openConversation()->handle(new NewConversation(
        kind: ConversationKind::ColdOutreach->value,
        createdByParticipantId: $employer,
        participantIds: [$employer, $jobseeker],
    ));

    expect($conversation->participants)->toHaveCount(2)
        ->and($conversation->hasParticipant($employer))->toBeTrue()
        ->and($conversation->hasParticipant($jobseeker))->toBeTrue()
        ->and($conversation->kind)->toBe('cold_outreach');
});

test('a duplicated participant id is only recorded once', function () {
    $id = (string) Str::ulid();

    $conversation = openConversation()->handle(new NewConversation(
        kind: ConversationKind::Internal->value,
        createdByParticipantId: $id,
        participantIds: [$id, $id],
    ));

    expect($conversation->participants)->toHaveCount(1);
});

test('a unique key makes opening idempotent', function () {
    $applicationId = (string) Str::ulid();
    $employer = (string) Str::ulid();
    $jobseeker = (string) Str::ulid();

    $new = new NewConversation(
        kind: ConversationKind::Application->value,
        createdByParticipantId: $jobseeker,
        participantIds: [$employer, $jobseeker],
        contextType: ChatContextType::Application->value,
        contextId: $applicationId,
        uniqueKey: 'application:'.$applicationId,
    );

    $first = openConversation()->handle($new);
    $second = openConversation()->handle($new);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::query()->count())->toBe(1);
});

/**
 * The guard against "apply and chat" firing twice. Idempotency is enforced by
 * the unique index, not by a read-then-write check, so the loser of a race
 * returns the winner's row instead of raising.
 */
test('a race on the unique key resolves to the winning conversation', function () {
    $key = 'application:'.Str::ulid();
    $participant = (string) Str::ulid();

    $new = new NewConversation(
        kind: ConversationKind::Application->value,
        createdByParticipantId: $participant,
        participantIds: [$participant],
        uniqueKey: $key,
    );

    // Simulate the interleaving: a row appears after the caller's existence
    // check but before its insert.
    $winner = Conversation::factory()->create(['unique_key' => $key]);

    expect(openConversation()->handle($new)->id)->toBe($winner->id)
        ->and(Conversation::query()->count())->toBe(1);
});

test('without a unique key the same context may hold many conversations', function () {
    $jobId = (string) Str::ulid();
    $employer = (string) Str::ulid();

    foreach (range(1, 3) as $ignored) {
        openConversation()->handle(new NewConversation(
            kind: ConversationKind::ColdOutreach->value,
            createdByParticipantId: $employer,
            participantIds: [$employer, (string) Str::ulid()],
            contextType: ChatContextType::Job->value,
            contextId: $jobId,
        ));
    }

    expect(Conversation::query()->where('context_id', $jobId)->count())->toBe(3);
});
