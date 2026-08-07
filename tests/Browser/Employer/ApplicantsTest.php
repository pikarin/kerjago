<?php

use App\Actions\Applications\ApplyToJob;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\CandidateUnlock;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The applicant inbox is where the unlock rules become something an employer can
 * see: a masked name, a dimmed contact line, a resume button that refuses to
 * open. tests/Feature/LockedApplicantsTest.php asserts the props behind all of
 * that; these assert that the page actually renders them.
 */
test('an employer moves an applicant through the pipeline from the list', function () {
    $profile = JobseekerProfile::factory()->create(['full_name' => 'Rina Kartika']);
    $job = activeJob();

    $application = app(ApplyToJob::class)->handle($profile, $job);

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$job->id}/applicants");

    assertPageEventuallyShows($page, 'Rina Kartika')
        ->assertSee('Submitted');

    // The status Select posts a PATCH and re-renders the row in place, so the
    // badge beside it is the only thing that reports the change.
    chooseFromSelect($page, 'Submitted', 'Shortlisted');

    assertPageEventuallyHides($page, 'Submitted')
        ->assertSee('Shortlisted')
        ->assertPathIs("/employer/jobs/{$job->id}/applicants");

    expect($application->refresh()->status)->toBe(ApplicationStatus::Shortlisted);
});

/**
 * The mask is the product rule that pays for the product, so it is asserted on
 * the rendered page rather than only in props: a locked applicant shows an
 * initial instead of a surname, and their CV button is present but dead.
 */
test('a locked applicant is masked and their resume stays shut', function () {
    Storage::fake('local');

    $profile = JobseekerProfile::factory()->create([
        'full_name' => 'Rina Kartika',
        'resume_path' => UploadedFile::fake()->create('resume.pdf', 10)->store('resumes', 'local'),
    ]);
    $job = activeJob();

    app(ApplyToJob::class)->handle($profile, $job);

    // Applying always issues an unlock while the job is under its ten-applicant
    // allowance; dropping it is how a test reaches the eleventh-applicant state
    // without seeding eleven of them.
    CandidateUnlock::query()->delete();

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$job->id}/applicants");

    assertPageEventuallyShows($page, 'Rina K.')
        ->assertDontSee('Kartika')
        ->assertVisible('@locked-badge')
        ->assertSee('Resume locked')
        ->assertButtonDisabled('Resume locked')
        ->assertNoJavaScriptErrors();
});

test('an unlocked applicant shows their full name and an open resume', function () {
    Storage::fake('local');

    $profile = JobseekerProfile::factory()->create([
        'full_name' => 'Rina Kartika',
        'resume_path' => UploadedFile::fake()->create('resume.pdf', 10)->store('resumes', 'local'),
    ]);
    $job = activeJob();

    app(ApplyToJob::class)->handle($profile, $job);

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$job->id}/applicants");

    // Asserted by hook, not by the word: "Locked" is a substring of the quota
    // line's "unlocked", and the plugin's text matching is case-insensitive.
    assertPageEventuallyShows($page, 'Rina Kartika')
        ->assertMissing('@locked-badge')
        ->assertDontSee('Resume locked')
        ->assertSeeLink('Resume');
});

test('the quota line reports how many unlocks the job has spent', function () {
    $job = activeJob();

    foreach (JobseekerProfile::factory()->count(2)->create() as $profile) {
        app(ApplyToJob::class)->handle($profile, $job);
    }

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$job->id}/applicants");

    assertPageEventuallyShows($page, '2 of 10 free unlocks used on this job');
});

test('a job nobody applied to says so', function () {
    $job = activeJob();

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$job->id}/applicants");

    assertPageEventuallyShows($page, 'No applicants yet')
        ->assertSee('0 of 10 free unlocks used on this job')
        ->assertNoJavaScriptErrors();

    expect(Application::query()->count())->toBe(0);
});

/**
 * An applicant's name is a link into the talent profile, which is the only route
 * an employer has from "who applied" to "what can they do".
 */
test('an applicant name opens their talent profile', function () {
    $profile = JobseekerProfile::factory()->create(['full_name' => 'Rina Kartika']);
    $job = activeJob();

    app(ApplyToJob::class)->handle($profile, $job);

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$job->id}/applicants");

    assertPageEventuallyShows($page, 'Rina Kartika')
        ->click('Rina Kartika');

    assertPageEventuallyShows($page, 'Rina Kartika')
        ->assertPathIs("/employer/talent/{$profile->id}")
        ->assertNoJavaScriptErrors();
});

test('an employer cannot open the applicants of a job that is not theirs', function () {
    $job = activeJob();
    $someoneElse = Job::factory()->create();

    $this->actingAs($job->employerProfile->user);

    $page = visit("/employer/jobs/{$someoneElse->id}/applicants");

    assertPageEventuallyShows($page, '403');
});
