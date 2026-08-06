<?php

use App\Actions\Chat\StartColdOutreach;
use App\Actions\Chat\StartInternalConversation;
use App\Actions\Unlocks\IssueCandidateUnlock;
use App\Chat\Models\Conversation;
use App\Enums\ConversationKind;
use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Cold outreach needs an active Candidate Unlock, so every test that expects a
 * thread to open has to grant one first.
 */
function unlockedTarget(EmployerProfile $employer): JobseekerProfile
{
    $target = JobseekerProfile::factory()->create();

    CandidateUnlock::factory()->create([
        'employer_profile_id' => $employer->id,
        'jobseeker_profile_id' => $target->id,
    ]);

    return $target;
}

test('cold outreach opens a conversation with no context', function () {
    $employer = EmployerProfile::factory()->create();
    $target = unlockedTarget($employer);

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
    $target = unlockedTarget($employer);

    $first = app(StartColdOutreach::class)->handle($employer->user, $target);
    $second = app(StartColdOutreach::class)->handle($employer->user, $target);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::query()->count())->toBe(1);
});

test('two employers reaching the same jobseeker get separate conversations', function () {
    $first = EmployerProfile::factory()->create();
    $second = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    foreach ([$first, $second] as $employer) {
        CandidateUnlock::factory()->create([
            'employer_profile_id' => $employer->id,
            'jobseeker_profile_id' => $target->id,
        ]);
    }

    app(StartColdOutreach::class)->handle($first->user, $target);
    app(StartColdOutreach::class)->handle($second->user, $target);

    expect(Conversation::query()->count())->toBe(2);
});

/**
 * Chat is a contact channel, so an employer with no unlock cannot open one —
 * otherwise cold outreach would route straight around the mask (ADR 0013).
 */
test('cold outreach is refused for a locked candidate', function () {
    $employer = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    expect(fn () => app(StartColdOutreach::class)->handle($employer->user, $target))
        ->toThrow(AuthorizationException::class);

    expect(Conversation::query()->count())->toBe(0);
});

/**
 * The sweep revokes the cold-outreach thread as well as application threads,
 * so re-unlocking has to restore both. StartColdOutreach is idempotent and
 * hands back the existing row, so a thread left revoked would 403 forever with
 * no way to recreate it.
 */
test('re-unlocking restores a revoked cold-outreach thread', function () {
    $employer = EmployerProfile::factory()->create();
    $target = unlockedTarget($employer);

    $conversation = app(StartColdOutreach::class)->handle($employer->user, $target);

    CandidateUnlock::query()->update(['expires_at' => now()->subDay()]);
    $this->artisan('unlocks:expire')->assertSuccessful();

    expect($conversation->fresh()?->hasParticipant($employer->user_id))->toBeFalse();

    app(IssueCandidateUnlock::class)->handle($employer, $target, now()->addYear());

    expect($conversation->fresh()?->hasParticipant($employer->user_id))->toBeTrue();
});

test('cold outreach is refused once the unlock expires', function () {
    $employer = EmployerProfile::factory()->create();
    $target = JobseekerProfile::factory()->create();

    CandidateUnlock::factory()->expired()->create([
        'employer_profile_id' => $employer->id,
        'jobseeker_profile_id' => $target->id,
    ]);

    expect(fn () => app(StartColdOutreach::class)->handle($employer->user, $target))
        ->toThrow(AuthorizationException::class);
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
