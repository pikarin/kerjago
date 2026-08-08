<?php

use App\Enums\JobStatus;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;

/**
 * The unverified employer's view of the product.
 *
 * The gates themselves are asserted server-side in
 * tests/Feature/UnverifiedEmployerGatesTest.php. What is only observable here is
 * that the client renders the *consequence* — that the banner, the parked
 * status and the Talent Search wall actually appear, rather than the page
 * silently behaving as though nothing were withheld.
 */
beforeEach(function () {
    $this->employer = EmployerProfile::factory()->unverified()->create([
        'company_name' => 'Kerjago Labs',
    ]);
});

test('an unverified employer is told why their ads are waiting', function () {
    Job::factory()->pending()->for($this->employer, 'employerProfile')->create([
        'title' => 'Staff Platform Engineer',
    ]);

    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs');

    assertPageEventuallyShows($page, 'Staff Platform Engineer')
        ->assertSee("Your company isn't verified yet")
        ->assertSee('Pending')
        ->assertNoJavaScriptErrors();
});

test('a parked ad offers no Publish button to click again', function () {
    Job::factory()->pending()->for($this->employer, 'employerProfile')->create([
        'title' => 'Staff Platform Engineer',
    ]);

    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs');

    // Publish has already been asked for and declined. Offering the button
    // again is a control that looks like it does something and cannot.
    assertPageEventuallyShows($page, 'Staff Platform Engineer')
        ->assertSee('Pending')
        ->assertDontSee('Publish')
        ->assertNoJavaScriptErrors();
});

test('publishing while unverified parks the ad and says so', function () {
    $job = Job::factory()->draft()->for($this->employer, 'employerProfile')->create([
        'title' => 'Staff Platform Engineer',
    ]);

    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs');

    assertPageEventuallyShows($page, 'Staff Platform Engineer')
        ->click('Publish');

    assertPageEventuallyShows($page, 'waiting to be published')
        ->assertNoJavaScriptErrors();

    expect($job->refresh()->status)->toBe(JobStatus::Pending)
        ->and($job->expires_at)->toBeNull();
});

test('talent search shows the wall instead of the whole pool', function () {
    JobseekerProfile::factory(20)->create();

    $this->actingAs($this->employer->user);

    $page = visit('/employer/talent');

    assertPageEventuallyShows($page, 'There are more candidates here')
        ->assertSee('Get verified')
        ->assertNoJavaScriptErrors();
});
