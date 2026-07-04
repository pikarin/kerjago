<?php

use App\Models\JobseekerProfile;
use App\Models\User;
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
]);
