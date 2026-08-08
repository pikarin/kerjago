<?php

use App\Actions\Applications\ApplyToJob;
use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;

test('the applicant list masks everyone past the tenth and reports the quota', function () {
    $job = Job::factory()->create();

    // A second apart each, so "newest first" is a fact about the data rather
    // than about how the database happened to break a timestamp tie.
    foreach (JobseekerProfile::factory()->count(11)->create() as $index => $profile) {
        $profile->update(['full_name' => "Applicant Number{$index}"]);
        app(ApplyToJob::class)->handle($profile->fresh(), $job->fresh());
        $this->travel(1)->seconds();
    }

    $this->actingAs($job->employerProfile->user)
        ->get(route('employer.jobs.applicants', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('unlockQuota.used', 10)
            ->where('unlockQuota.total', 10)
            // Newest first, so the eleventh applicant heads the list.
            ->where('applications.data.0.profile.is_locked', true)
            ->where('applications.data.0.profile.full_name', 'Applicant N.')
            ->where('applications.data.0.can_download_resume', false)
            ->where('applications.data.1.profile.is_locked', false)
            ->where('applications.data.1.profile.full_name', 'Applicant Number9')
        );
});

test('the employer inbox shows locked applicants as static teasers only', function () {
    $job = Job::factory()->create();
    $profile = JobseekerProfile::factory()->create(['full_name' => 'Rina Kartika']);

    app(ApplyToJob::class)->handle($profile, $job);
    CandidateUnlock::query()->delete();

    $this->actingAs($job->employerProfile->user)
        ->get(route('chat.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('lockedApplicants', 1)
            ->where('lockedApplicants.0.display_name', 'Rina K.')
            ->where('lockedApplicants.0.job_title', $job->title)
            ->missing('lockedApplicants.0.unread_count')
            ->missing('lockedApplicants.0.last_message_at')
        )
        ->assertDontSee('Kartika');
});

test('an unlocked applicant drops off the locked teaser list', function () {
    $job = Job::factory()->create();
    $profile = JobseekerProfile::factory()->create();

    app(ApplyToJob::class)->handle($profile, $job);

    $this->actingAs($job->employerProfile->user)
        ->get(route('chat.index'))
        ->assertInertia(fn ($page) => $page->has('lockedApplicants', 0));
});

test('a jobseeker never sees a locked applicant list', function () {
    $profile = JobseekerProfile::factory()->create();

    $this->actingAs($profile->user)
        ->get(route('chat.index'))
        ->assertInertia(fn ($page) => $page->where('lockedApplicants', []));
});

test('another employer sees no teaser for a candidate who applied elsewhere', function () {
    $job = Job::factory()->create();
    app(ApplyToJob::class)->handle(JobseekerProfile::factory()->create(), $job);
    CandidateUnlock::query()->delete();

    $this->actingAs(EmployerProfile::factory()->verified()->create()->user)
        ->get(route('chat.index'))
        ->assertInertia(fn ($page) => $page->has('lockedApplicants', 0));
});
