<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('jobseeker can apply to an active job with a resume snapshot', function () {
    Storage::fake('local');

    $profile = JobseekerProfile::factory()->create([
        'resume_path' => UploadedFile::fake()->create('resume.pdf', 10)->store('resumes', 'local'),
    ]);
    $job = Job::factory()->create();

    $this->actingAs($profile->user)
        ->post(route('jobseeker.jobs.apply', $job), ['cover_note' => 'Excited to join.'])
        ->assertRedirect(route('jobseeker.applications.index', absolute: false));

    $application = Application::query()->whereBelongsTo($profile)->firstOrFail();

    expect($application->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->cover_note)->toBe('Excited to join.')
        ->and($application->resume_path)->not->toBeNull()
        ->and($application->resume_path)->not->toBe($profile->resume_path);

    Storage::disk('local')->assertExists($application->resume_path);
});

test('a jobseeker cannot apply to the same job twice', function () {
    $application = Application::factory()->create();

    $this->actingAs($application->jobseekerProfile->user)
        ->post(route('jobseeker.jobs.apply', $application->job))
        ->assertSessionHasErrors('job');

    expect(Application::query()->count())->toBe(1);
});

test('applications to non-active jobs are rejected', function (string $state) {
    $profile = JobseekerProfile::factory()->create();
    $job = Job::factory()->{$state}()->create();

    $this->actingAs($profile->user)
        ->post(route('jobseeker.jobs.apply', $job))
        ->assertSessionHasErrors('job');
})->with(['draft', 'closed']);

test('jobseeker without a profile is redirected to profile setup when applying', function () {
    $job = Job::factory()->create();

    $this->actingAs(User::factory()->jobseeker()->create())
        ->post(route('jobseeker.jobs.apply', $job))
        ->assertRedirect(route('jobseeker.profile.edit', absolute: false));
});

test('jobseeker can track their applications', function () {
    $profile = JobseekerProfile::factory()->create();
    Application::factory(2)->recycle($profile)->create();
    Application::factory()->create();

    $this->actingAs($profile->user)
        ->get(route('jobseeker.applications.index'))
        ->assertInertia(fn ($page) => $page
            ->component('jobseeker/applications/Index')
            ->has('applications.data', 2)
        );
});

test('employer can view applicants for their own job', function () {
    $application = Application::factory()->create();

    $this->actingAs($application->job->employerProfile->user)
        ->get(route('employer.jobs.applicants', $application->job))
        ->assertInertia(fn ($page) => $page
            ->component('employer/jobs/Applicants')
            ->has('applications.data', 1)
        );
});

test('employer cannot view applicants for another employer\'s job', function () {
    $application = Application::factory()->create();
    $otherEmployer = EmployerProfile::factory()->create();

    $this->actingAs($otherEmployer->user)
        ->get(route('employer.jobs.applicants', $application->job))
        ->assertForbidden();
});

test('employer can update an applicant\'s status', function () {
    $application = Application::factory()->create();

    $this->actingAs($application->job->employerProfile->user)
        ->patch(route('employer.applications.status.update', $application), [
            'status' => 'shortlisted',
        ])
        ->assertRedirect();

    expect($application->refresh()->status)->toBe(ApplicationStatus::Shortlisted);
});

test('employer cannot update statuses on another employer\'s applicants', function () {
    $application = Application::factory()->create();
    $otherEmployer = EmployerProfile::factory()->create();

    $this->actingAs($otherEmployer->user)
        ->patch(route('employer.applications.status.update', $application), [
            'status' => 'rejected',
        ])
        ->assertForbidden();

    expect($application->refresh()->status)->toBe(ApplicationStatus::Submitted);
});

test('the job owner and the applicant can download the resume snapshot', function () {
    Storage::fake('local');

    $application = Application::factory()->create([
        'resume_path' => UploadedFile::fake()->create('snapshot.pdf', 10)->store('applications', 'local'),
    ]);

    $this->actingAs($application->job->employerProfile->user)
        ->get(route('applications.resume', $application))
        ->assertOk();

    $this->actingAs($application->jobseekerProfile->user)
        ->get(route('applications.resume', $application))
        ->assertOk();
});

test('other employers cannot download the resume snapshot', function () {
    Storage::fake('local');

    $application = Application::factory()->create([
        'resume_path' => UploadedFile::fake()->create('snapshot.pdf', 10)->store('applications', 'local'),
    ]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('applications.resume', $application))
        ->assertForbidden();
});
