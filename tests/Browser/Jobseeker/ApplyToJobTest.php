<?php

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * The apply flow is the one jobseeker journey that only exists in the browser:
 * the button opens a Reka dialog, the cover note is bound with v-model, and the
 * submit is a useForm POST whose success closes the dialog and swaps the card
 * for a badge. tests/Feature/ApplicationTest.php posts straight to the route and
 * so asserts none of that.
 */
test('a jobseeker with a profile applies from the job page', function () {
    Storage::fake('local');

    $profile = JobseekerProfile::factory()->create([
        'resume_path' => UploadedFile::fake()->create('resume.pdf', 10)->store('resumes', 'local'),
    ]);
    $job = activeJob();

    $this->actingAs($profile->user);

    $page = visit("/jobs/{$job->id}");

    assertPageEventuallyShows($page, 'Apply now')
        ->click('Apply now');

    assertPageEventuallyShows($page, 'Cover note (optional)')
        ->fill('cover_note', 'I have shipped Laravel in production for six years.')
        ->click('Submit application');

    assertPageEventuallyShows($page, 'Application submitted')
        ->assertDontSee('Apply now')
        ->assertNoJavaScriptErrors();

    $application = Application::query()->whereBelongsTo($profile)->sole();

    expect($application->job_id)->toBe($job->id)
        ->and($application->status)->toBe(ApplicationStatus::Submitted)
        ->and($application->cover_note)->toBe('I have shipped Laravel in production for six years.');
});

/**
 * A rejected apply keeps the dialog mounted, so the error has to surface inside
 * it rather than by navigating. An over-long cover note is the provocation that
 * leaves the rest of the page untouched: the job stays active and `has_applied`
 * stays false, so Vue re-renders the very card that holds the dialog.
 *
 * The two guards in ApplyToJob — closed job, duplicate application — cannot be
 * asserted here. Both redirect back to a page that no longer renders the dialog
 * (a closed job 404s the public show route; a duplicate flips `has_applied` and
 * swaps the card for the badge), so their messages have no dialog to appear in.
 * tests/Feature/ApplicationTest.php covers both.
 */
test('a rejected application reports itself inside the dialog', function () {
    $profile = JobseekerProfile::factory()->create();
    $job = activeJob();

    $this->actingAs($profile->user);

    $page = visit("/jobs/{$job->id}");

    assertPageEventuallyShows($page, 'Apply now')
        ->click('Apply now');

    assertPageEventuallyShows($page, 'Cover note (optional)')
        ->fill('cover_note', str_repeat('a', 2001))
        ->click('Submit application');

    assertPageEventuallyShows($page, 'must not be greater than 2000 characters')
        ->assertSee('Submit application')
        ->assertDontSee('Application submitted');

    expect(Application::query()->count())->toBe(0);
});

test('a jobseeker without a profile is sent to complete it first', function () {
    $job = activeJob();

    $this->actingAs(User::factory()->jobseeker()->create());

    $page = visit("/jobs/{$job->id}");

    assertPageEventuallyShows($page, 'Complete your profile before applying.')
        ->assertDontSee('Apply now')
        ->click('Complete profile');

    assertPageEventuallyShows($page, 'Your profile')
        ->assertPathIs('/profile');
});

test('a guest is offered a login rather than an apply button', function () {
    $job = activeJob();

    $page = visit("/jobs/{$job->id}");

    assertPageEventuallyShows($page, 'Log in as a jobseeker to apply for this role.')
        ->assertDontSee('Apply now')
        ->click('Log in to apply');

    assertPageEventuallyShows($page, 'Log in to your account')
        ->assertPathIs('/login');
});

/**
 * An employer browsing the public board is neither of the cases above: they are
 * already signed in, so nothing should suggest they log in, and they cannot
 * apply either.
 */
test('an employer sees no way to apply', function () {
    $employer = EmployerProfile::factory()->verified()->create();
    $job = activeJob();

    $this->actingAs($employer->user);

    $page = visit("/jobs/{$job->id}");

    assertPageEventuallyShows($page, 'Log in as a jobseeker to apply for this role.')
        ->assertDontSee('Apply now');
});
