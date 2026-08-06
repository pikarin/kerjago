<?php

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\JobseekerProfile;
use App\Models\User;

test('publishing stamps a 45-day window', function () {
    $job = Job::factory()->draft()->create();

    $this->actingAs($job->employerProfile->user)
        ->post(route('employer.jobs.publish', $job))
        ->assertRedirect(route('employer.jobs.index', absolute: false));

    $job->refresh();

    expect($job->status)->toBe(JobStatus::Active)
        ->and($job->published_at)->not->toBeNull()
        ->and($job->expires_at?->toDateString())
        ->toBe($job->published_at?->addDays(Job::PUBLISH_WINDOW_DAYS)->toDateString());
});

test('editing a live job does not extend its window', function () {
    $job = Job::factory()->create();
    $originalExpiry = $job->expires_at;

    $this->actingAs($job->employerProfile->user)
        ->put(route('employer.jobs.update', $job), [
            'title' => 'Retitled role',
            'description' => $job->description,
            'skills' => $job->skills,
            'location_country' => $job->location_country,
            'location_city' => $job->location_city,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'currency' => $job->currency->value,
            'employment_type' => $job->employment_type?->value,
            'work_arrangement' => $job->work_arrangement?->value,
            'experience_level' => $job->experience_level?->value,
            'education_level' => $job->education_level?->value,
            'status' => 'draft',
        ])->assertRedirect();

    expect($job->refresh()->expires_at?->toIso8601String())->toBe($originalExpiry?->toIso8601String());
});

test('a live job can be edited without being taken offline first', function () {
    $job = Job::factory()->create();

    $this->actingAs($job->employerProfile->user)
        ->put(route('employer.jobs.update', $job), [
            'title' => 'Retitled while live',
            'description' => $job->description,
            'skills' => $job->skills,
            'location_country' => $job->location_country,
            'location_city' => $job->location_city,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'currency' => $job->currency->value,
            'employment_type' => $job->employment_type?->value,
            'work_arrangement' => $job->work_arrangement?->value,
            'experience_level' => $job->experience_level?->value,
            'education_level' => $job->education_level?->value,
            // The status it already has: a no-op, not a transition.
            'status' => 'active',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($job->refresh()->title)->toBe('Retitled while live')
        ->and($job->status)->toBe(JobStatus::Active);
});

test('a draft cannot be pushed live through the edit form', function () {
    $job = Job::factory()->draft()->create();

    $this->actingAs($job->employerProfile->user)
        ->put(route('employer.jobs.update', $job), [
            'title' => $job->title,
            'description' => $job->description,
            'skills' => $job->skills,
            'location_country' => $job->location_country,
            'location_city' => $job->location_city,
            'salary_min' => $job->salary_min,
            'salary_max' => $job->salary_max,
            'currency' => $job->currency->value,
            'employment_type' => $job->employment_type?->value,
            'work_arrangement' => $job->work_arrangement?->value,
            'experience_level' => $job->experience_level?->value,
            'education_level' => $job->education_level?->value,
            'status' => 'active',
        ])
        ->assertSessionHasErrors('status');

    expect($job->refresh()->status)->toBe(JobStatus::Draft);
});

test('publishing an already-live ad is a no-op, not a second window', function () {
    $job = Job::factory()->create();
    $originalExpiry = $job->expires_at;

    $this->actingAs($job->employerProfile->user)
        ->post(route('employer.jobs.publish', $job))
        ->assertRedirect();

    expect($job->refresh()->expires_at?->toIso8601String())
        ->toBe($originalExpiry?->toIso8601String());
});

/**
 * The status column is editable and the timestamps are not, so taking a live ad
 * back to Draft and publishing it again must not buy a fresh 45 days — it
 * resumes on the days it had left.
 */
test('a draft round trip does not restamp a running window', function () {
    $job = Job::factory()->create(['expires_at' => now()->addDays(10)]);
    $originalExpiry = $job->expires_at;

    $job->update(['status' => 'draft']);

    $this->actingAs($job->employerProfile->user)
        ->post(route('employer.jobs.publish', $job))
        ->assertRedirect();

    $job->refresh();

    expect($job->status)->toBe(JobStatus::Active)
        ->and($job->expires_at?->toIso8601String())->toBe($originalExpiry?->toIso8601String());
});

test('a closed round trip does not restamp a running window either', function () {
    $job = Job::factory()->create(['expires_at' => now()->addDays(3)]);
    $originalExpiry = $job->expires_at;

    $job->update(['status' => 'closed']);

    $this->actingAs($job->employerProfile->user)
        ->post(route('employer.jobs.publish', $job));

    expect($job->refresh()->expires_at?->toIso8601String())
        ->toBe($originalExpiry?->toIso8601String());
});

test('an expired ad can be re-published for a fresh window', function () {
    $job = Job::factory()->expired()->create();

    $this->actingAs($job->employerProfile->user)
        ->post(route('employer.jobs.publish', $job))
        ->assertRedirect();

    expect($job->refresh()->status)->toBe(JobStatus::Active)
        ->and($job->expires_at?->isFuture())->toBeTrue();
});

test('an expired ad is not viewable, not appliable and not indexed', function () {
    $job = Job::factory()->expired()->create();

    $this->get(route('jobs.show', $job))->assertNotFound();

    expect($job->shouldBeSearchable())->toBeFalse()
        ->and(Job::query()->active()->count())->toBe(0);

    $profile = JobseekerProfile::factory()->create();

    $this->actingAs($profile->user)
        ->post(route('jobseeker.jobs.apply', $job), ['cover_note' => 'Hello'])
        ->assertSessionHasErrors('job');
});

test('an ad past its expiry is refused even before the sweep runs', function () {
    // Still flagged active: the daily command has not caught it yet, and the
    // read path must not wait for it.
    $job = Job::factory()->overdue()->create();

    $this->get(route('jobs.show', $job))->assertNotFound();
});

test('jobs:expire flips overdue ads and leaves live ones alone', function () {
    $overdue = Job::factory()->overdue()->create();
    $live = Job::factory()->create();

    $this->artisan('jobs:expire')->assertSuccessful();

    expect($overdue->refresh()->status)->toBe(JobStatus::Expired)
        ->and($live->refresh()->status)->toBe(JobStatus::Active);
});

test('a jobseeker cannot see another employer\'s expired ad either', function () {
    $job = Job::factory()->expired()->create();

    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('jobs.show', $job))
        ->assertNotFound();
});
