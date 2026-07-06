<?php

use App\Enums\Availability;
use App\Models\JobseekerProfile;
use App\Models\User;
use App\Models\WorkExperience;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('jobseeker can view the profile form', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('jobseeker.profile.edit'))
        ->assertOk();
});

test('employer cannot access the jobseeker profile form', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('jobseeker.profile.edit'))
        ->assertForbidden();
});

test('jobseeker can create a profile with a resume', function () {
    Storage::fake('local');

    $user = User::factory()->jobseeker()->create();

    $response = $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'skills' => ['PHP', 'Laravel'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
        'phone' => '+628123456789',
        'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    $profile = JobseekerProfile::query()->whereBelongsTo($user)->firstOrFail();

    expect($profile->skills)->toBe(['PHP', 'Laravel'])
        ->and($profile->resume_path)->not->toBeNull();

    Storage::disk('local')->assertExists($profile->resume_path);
});

test('uploading a new resume replaces the old file', function () {
    Storage::fake('local');

    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create([
        'resume_path' => UploadedFile::fake()->create('old.pdf', 10)->store('resumes', 'local'),
    ]);

    $oldPath = $profile->resume_path;

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'resume' => UploadedFile::fake()->create('new.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($profile->refresh()->resume_path);
});

test('jobseeker can save preference fields', function () {
    $user = User::factory()->jobseeker()->create();

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'preferred_job_title' => 'Engineering Manager',
        'skills' => ['PHP'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
        'preferred_country' => 'SG',
        'preferred_city' => 'Singapore',
        'availability' => 'two_weeks',
        'languages' => ['id', 'en'],
        'gender' => 'female',
        'education_level' => 'bachelor',
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    $profile = JobseekerProfile::query()->whereBelongsTo($user)->firstOrFail();

    expect($profile->preferred_job_title)->toBe('Engineering Manager')
        ->and($profile->preferred_country)->toBe('SG')
        ->and($profile->availability)->toBe(Availability::TwoWeeks)
        ->and($profile->languages)->toBe(['id', 'en']);
});

test('a profile still saves without any of the new optional fields', function () {
    $user = User::factory()->jobseeker()->create();

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'skills' => ['PHP'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    expect(JobseekerProfile::query()->whereBelongsTo($user)->exists())->toBeTrue();
});

test('work experiences are created, updated, and deleted from the submitted list', function () {
    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create();
    $kept = WorkExperience::factory()->for($profile, 'jobseekerProfile')->create(['job_title' => 'Junior Cook']);
    $removed = WorkExperience::factory()->for($profile, 'jobseekerProfile')->create(['job_title' => 'Dishwasher']);

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'experiences' => [
            ['id' => $kept->id, 'job_title' => 'Line Cook', 'company_name' => $kept->company_name, 'start_date' => '2020-01-01', 'end_date' => '2022-06-01'],
            ['id' => null, 'job_title' => 'Sous Chef', 'company_name' => 'Warung Enak', 'start_date' => '2022-07-01', 'end_date' => null],
        ],
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    $titles = $profile->refresh()->workExperiences->pluck('job_title');

    expect($titles)->toContain('Line Cook')
        ->and($titles)->toContain('Sous Chef')
        ->and($titles)->not->toContain('Dishwasher')
        ->and($kept->refresh()->job_title)->toBe('Line Cook')
        ->and(WorkExperience::query()->find($removed->id))->toBeNull();
});

test("a foreign experience id cannot be adopted into another jobseeker's profile", function () {
    $victim = WorkExperience::factory()->create(['job_title' => 'CFO']);

    $user = User::factory()->jobseeker()->create();
    $profile = JobseekerProfile::factory()->for($user)->create();

    $this->actingAs($user)->put(route('jobseeker.profile.update'), [
        'full_name' => $profile->full_name,
        'current_title' => $profile->current_title,
        'skills' => $profile->skills,
        'experience_years' => $profile->experience_years,
        'country' => $profile->country,
        'city' => $profile->city,
        'experiences' => [
            ['id' => $victim->id, 'job_title' => 'Hijacked', 'company_name' => 'Evil Co', 'start_date' => '2020-01-01', 'end_date' => null],
        ],
    ])->assertRedirect(route('jobseeker.profile.edit', absolute: false));

    // The victim's row is untouched; the submitted row lands as a new record.
    expect($victim->refresh()->job_title)->toBe('CFO')
        ->and($victim->jobseeker_profile_id)->not->toBe($profile->id)
        ->and($profile->refresh()->workExperiences->pluck('job_title'))->toContain('Hijacked');
});

test('profile validation rejects bad input', function (array $overrides, string $errorField) {
    $user = User::factory()->jobseeker()->create();

    $valid = [
        'full_name' => 'Siti Rahma',
        'current_title' => 'Backend Engineer',
        'skills' => ['PHP'],
        'experience_years' => 5,
        'country' => 'ID',
        'city' => 'Jakarta',
    ];

    $this->actingAs($user)
        ->put(route('jobseeker.profile.update'), [...$valid, ...$overrides])
        ->assertSessionHasErrors($errorField);
})->with([
    'empty skills' => [['skills' => []], 'skills'],
    'unknown country' => [['country' => 'US'], 'country'],
    'invalid phone' => [['phone' => 'not-a-phone'], 'phone'],
    'negative experience' => [['experience_years' => -1], 'experience_years'],
    'unknown availability' => [['availability' => 'someday'], 'availability'],
    'unknown language' => [['languages' => ['fr']], 'languages.0'],
    'experience missing title' => [
        ['experiences' => [['job_title' => '', 'company_name' => 'Co', 'start_date' => '2020-01-01']]],
        'experiences.0.job_title',
    ],
    'experience ends before it starts' => [
        ['experiences' => [['job_title' => 'Cook', 'company_name' => 'Co', 'start_date' => '2022-01-01', 'end_date' => '2020-01-01']]],
        'experiences.0.end_date',
    ],
    'experience starts in the future' => [
        ['experiences' => [['job_title' => 'Cook', 'company_name' => 'Co', 'start_date' => '2090-01-01']]],
        'experiences.0.start_date',
    ],
]);
