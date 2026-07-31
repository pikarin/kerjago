<?php

use App\Actions\Chat\StartColdOutreach;
use App\Actions\Chat\StartInternalConversation;
use App\Chat\Models\Conversation;
use App\Enums\ConversationKind;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('cold outreach opens a conversation with no context', function () {
    $employer = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    $conversation = app(StartColdOutreach::class)->handle($employer->user, $target);

    expect($conversation->kind)->toBe(ConversationKind::ColdOutreach->value)
        ->and($conversation->context_type)->toBeNull()
        ->and($conversation->context_id)->toBeNull()
        ->and($conversation->hasParticipant($employer->user_id))->toBeTrue()
        ->and($conversation->hasParticipant($target->user_id))->toBeTrue();
});

/**
 * Repeated clicks must reopen the existing thread rather than fragment the
 * history across duplicates.
 */
test('cold outreach is idempotent per employer and jobseeker pair', function () {
    $employer = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    $first = app(StartColdOutreach::class)->handle($employer->user, $target);
    $second = app(StartColdOutreach::class)->handle($employer->user, $target);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::query()->count())->toBe(1);
});

test('two employers reaching the same jobseeker get separate conversations', function () {
    $first = EmployerProfile::factory()->create();
    $second = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    app(StartColdOutreach::class)->handle($first->user, $target);
    app(StartColdOutreach::class)->handle($second->user, $target);

    expect(Conversation::query()->count())->toBe(2);
});

/**
 * Cold outreach is deliberately ungated for now — no consent flag, no rate
 * limit, no blocking. This pins that as the current behaviour so a future gate
 * is a visible change rather than a silent one.
 */
test('cold outreach is currently ungated', function () {
    $employer = EmployerProfile::factory()->create();

    foreach (JobseekerProfile::factory()->count(5)->create() as $target) {
        app(StartColdOutreach::class)->handle($employer->user, $target);
    }

    expect(Conversation::query()->count())->toBe(5);
});

test('staff can open an internal conversation with either side', function () {
    $staff = User::factory()->staff()->create();
    $employer = EmployerProfile::factory()->create();
    $jobseeker = JobseekerProfile::factory()->create();

    $withEmployer = app(StartInternalConversation::class)->handle($staff, $employer->user);
    $withJobseeker = app(StartInternalConversation::class)->handle($staff, $jobseeker->user);

    expect($withEmployer->kind)->toBe(ConversationKind::Internal->value)
        ->and($withEmployer->hasParticipant($staff->id))->toBeTrue()
        ->and($withJobseeker->hasParticipant($staff->id))->toBeTrue()
        ->and(Conversation::query()->count())->toBe(2);
});

test('a non-staff user cannot open an internal conversation', function () {
    $employer = EmployerProfile::factory()->create();
    $jobseeker = JobseekerProfile::factory()->create();

    expect(fn () => app(StartInternalConversation::class)->handle($employer->user, $jobseeker->user))
        ->toThrow(ValidationException::class);

    expect(Conversation::query()->count())->toBe(0);
});

test('internal conversations are idempotent per pair', function () {
    $staff = User::factory()->staff()->create();
    $jobseeker = JobseekerProfile::factory()->create();

    $first = app(StartInternalConversation::class)->handle($staff, $jobseeker->user);
    $second = app(StartInternalConversation::class)->handle($staff, $jobseeker->user);

    expect($second->id)->toBe($first->id);
});
