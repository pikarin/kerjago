<?php

use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkArrangement;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;

function validJobPayload(array $overrides = []): array
{
    return [
        'title' => 'Senior Laravel Developer',
        'description' => 'Build and scale our recruitment platform.',
        'skills' => ['PHP', 'Laravel'],
        'location_country' => 'ID',
        'location_city' => 'Jakarta',
        'salary_min' => 15_000_000,
        'salary_max' => 30_000_000,
        'currency' => 'IDR',
        'employment_type' => 'full_time',
        'work_arrangement' => 'remote',
        'experience_level' => 'senior',
        'education_level' => 'bachelor',
        'status' => 'active',
        ...$overrides,
    ];
}

test('employer without a profile is redirected to profile setup', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('employer.jobs.index'))
        ->assertRedirect(route('employer.profile.edit', absolute: false));
});

test('employer with a profile can post a job', function () {
    $profile = EmployerProfile::factory()->create();

    $this->actingAs($profile->user)
        ->post(route('employer.jobs.store'), validJobPayload())
        ->assertRedirect(route('employer.jobs.index', absolute: false));

    $job = Job::query()->whereBelongsTo($profile)->firstOrFail();

    expect($job->status)->toBe(JobStatus::Active)
        ->and($job->salary_min)->toBe(15_000_000);
});

test('job validation rejects bad input', function (array $overrides, string $errorField) {
    $profile = EmployerProfile::factory()->create();

    $this->actingAs($profile->user)
        ->post(route('employer.jobs.store'), validJobPayload($overrides))
        ->assertSessionHasErrors($errorField);
})->with([
    'salary max below min' => [['salary_max' => 1], 'salary_max'],
    'unknown currency' => [['currency' => 'USD'], 'currency'],
    'unknown country' => [['location_country' => 'US'], 'location_country'],
    'no skills' => [['skills' => []], 'skills'],
    'bad status' => [['status' => 'archived'], 'status'],
    'missing employment type' => [['employment_type' => null], 'employment_type'],
    'missing work arrangement' => [['work_arrangement' => null], 'work_arrangement'],
    'missing experience level' => [['experience_level' => null], 'experience_level'],
    'missing education level' => [['education_level' => null], 'education_level'],
]);

test('posting a job persists the facet fields', function () {
    $profile = EmployerProfile::factory()->create();

    $this->actingAs($profile->user)
        ->post(route('employer.jobs.store'), validJobPayload());

    $job = Job::query()->whereBelongsTo($profile)->firstOrFail();

    expect($job->employment_type)->toBe(EmploymentType::FullTime)
        ->and($job->work_arrangement)->toBe(WorkArrangement::Remote)
        ->and($job->experience_level)->toBe(ExperienceLevel::Senior)
        ->and($job->education_level)->toBe(EducationLevel::Bachelor);
});

test('updating a legacy job requires the facet fields', function () {
    $job = Job::factory()->legacy()->create();

    $payload = validJobPayload();
    unset($payload['employment_type'], $payload['work_arrangement'], $payload['experience_level'], $payload['education_level']);

    $this->actingAs($job->employerProfile->user)
        ->put(route('employer.jobs.update', $job), $payload)
        ->assertSessionHasErrors(['employment_type', 'work_arrangement', 'experience_level', 'education_level']);

    $this->actingAs($job->employerProfile->user)
        ->put(route('employer.jobs.update', $job), validJobPayload())
        ->assertRedirect(route('employer.jobs.index', absolute: false));

    expect($job->refresh()->employment_type)->toBe(EmploymentType::FullTime);
});

test('employer can update their own job', function () {
    $job = Job::factory()->create();

    $this->actingAs($job->employerProfile->user)
        ->put(route('employer.jobs.update', $job), validJobPayload(['status' => 'closed']))
        ->assertRedirect(route('employer.jobs.index', absolute: false));

    expect($job->refresh()->status)->toBe(JobStatus::Closed);
});

test('employer cannot update another employer\'s job', function () {
    $job = Job::factory()->create();
    $otherEmployer = EmployerProfile::factory()->create();

    $this->actingAs($otherEmployer->user)
        ->put(route('employer.jobs.update', $job), validJobPayload())
        ->assertForbidden();
});

test('jobseeker cannot access employer job management', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('employer.jobs.index'))
        ->assertForbidden();
});

test('employer job list shows only their own jobs', function () {
    $profile = EmployerProfile::factory()->create();
    Job::factory(2)->recycle($profile)->create();
    Job::factory()->create();

    $this->actingAs($profile->user)
        ->get(route('employer.jobs.index'))
        ->assertInertia(fn ($page) => $page
            ->component('employer/jobs/Index')
            ->has('jobs.data', 2)
        );
});
