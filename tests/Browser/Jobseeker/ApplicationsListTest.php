<?php

use App\Actions\Applications\ApplyToJob;
use App\Enums\ApplicationStatus;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;

/**
 * A jobseeker's own view of their pipeline. The dashboard and the applications
 * table read the same data through different props, so they are asserted
 * together: a status the employer set has to reach both.
 */
test('an application and its status reach both the dashboard and the list', function () {
    $profile = JobseekerProfile::factory()->create();
    $job = activeJob(['title' => 'Senior Laravel Developer']);
    $company = $job->employerProfile->company_name;

    $application = app(ApplyToJob::class)->handle($profile, $job);
    $application->update(['status' => ApplicationStatus::Shortlisted]);

    $this->actingAs($profile->user);

    $page = visit('/dashboard');

    assertPageEventuallyShows($page, 'Overview')
        ->assertSee('Senior Laravel Developer')
        ->assertSee($company)
        ->assertSee('Shortlisted')
        ->click('View all');

    assertPageEventuallyShows($page, 'My applications')
        ->assertPathIs('/applications')
        ->assertSee('Senior Laravel Developer')
        ->assertSee($company)
        ->assertSee('Shortlisted')
        ->assertNoJavaScriptErrors();
});

test('a jobseeker who has applied to nothing is pointed at the job board', function () {
    $profile = JobseekerProfile::factory()->create();

    $this->actingAs($profile->user);

    $page = visit('/applications');

    assertPageEventuallyShows($page, 'No applications yet')
        ->click('Find jobs');

    assertPageEventuallyShows($page, 'Find your next role')
        ->assertPathIs('/jobs')
        ->assertNoJavaScriptErrors();
});

/**
 * The applications route sits behind `profile.complete`, which redirects rather
 * than refuses — a jobseeker with no profile lands on the form with an
 * explanation instead of on a 403.
 */
test('a jobseeker without a profile is redirected to the profile form', function () {
    $this->actingAs(User::factory()->jobseeker()->create());

    $page = visit('/applications');

    assertPageEventuallyShows($page, 'Your profile')
        ->assertPathIs('/profile');
});

test('an employer has no jobseeker applications page at all', function () {
    $this->actingAs(EmployerProfile::factory()->create()->user);

    $page = visit('/applications');

    assertPageEventuallyShows($page, '403');
});
