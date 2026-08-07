<?php

use App\Enums\JobStatus;
use App\Models\EmployerProfile;
use App\Models\Job;

/**
 * Posting a job is the employer journey with the most client-side surface: eight
 * Reka `<Select>` listboxes that are not `<select>` elements at all, a skills
 * input that turns keystrokes into an array, and a useForm submit that carries
 * the whole lot. tests/Feature/JobManagementTest.php posts an already-assembled
 * payload, so nothing above the request body is covered there.
 */
beforeEach(function () {
    $this->employer = EmployerProfile::factory()->verified()->create([
        'company_name' => 'Kerjago Labs',
    ]);
});

test('an employer posts a job through the form', function () {
    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs/create');

    assertPageEventuallyShows($page, 'Post a job')
        ->fill('title', 'Staff Platform Engineer')
        ->fill('description', 'Own the deployment pipeline end to end.')
        // The skills input commits its draft on Enter, so each skill is a
        // separate round of typing rather than one comma-joined value.
        ->fill('[placeholder="Type a skill and press Enter"]', 'Laravel')
        ->keys('[placeholder="Type a skill and press Enter"]', 'Enter')
        ->fill('[placeholder="Type a skill and press Enter"]', 'Kubernetes')
        ->keys('[placeholder="Type a skill and press Enter"]', 'Enter')
        ->fill('location_city', 'Jakarta')
        ->fill('salary_min', '30000000')
        ->fill('salary_max', '45000000');

    chooseFromSelect($page, 'Select country', 'Indonesia');
    chooseFromSelect($page, 'Currency', 'IDR');
    chooseFromSelect($page, 'Select type', 'Full-time');
    chooseFromSelect($page, 'Select arrangement', 'Remote');
    chooseFromSelect($page, 'Select level', 'Senior');
    chooseFromSelect($page, 'Select education', 'Bachelor');

    $page->click('Post job');

    assertPageEventuallyShows($page, 'Staff Platform Engineer')
        ->assertPathIs('/employer/jobs')
        ->assertSee('Draft')
        ->assertNoJavaScriptErrors();

    $job = Job::query()->whereBelongsTo($this->employer)->sole();

    expect($job->title)->toBe('Staff Platform Engineer')
        ->and($job->skills)->toBe(['Laravel', 'Kubernetes'])
        ->and($job->location_city)->toBe('Jakarta')
        ->and($job->salary_min)->toBe(30_000_000)
        ->and($job->status)->toBe(JobStatus::Draft)
        ->and($job->expires_at)->toBeNull();
});

/**
 * The form reports server-side failures without leaving the page, so a rejected
 * submit has to put the message beside the field rather than anywhere else.
 */
test('the form keeps the employer on the page and names the missing fields', function () {
    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs/create');

    assertPageEventuallyShows($page, 'Post a job')
        ->click('Post job');

    assertPageEventuallyShows($page, 'The title field is required.')
        ->assertSee('The description field is required.')
        ->assertPathIs('/employer/jobs/create');

    expect(Job::query()->count())->toBe(0);
});

/**
 * Publishing is deliberately not a status the form can send — it stamps the
 * 45-day expiry clock, so it has its own button and its own endpoint (ADR 0013).
 * The row updates in place via a preserveScroll visit, which is the part only a
 * browser can check.
 */
test('publishing a draft flips the row to active and stamps an expiry', function () {
    $job = Job::factory()->draft()->recycle($this->employer)->create([
        'title' => 'Staff Platform Engineer',
    ]);

    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs');

    assertPageEventuallyShows($page, 'Staff Platform Engineer')
        ->assertSee('Draft')
        ->assertSee('—')
        ->click('Publish');

    assertPageEventuallyHides($page, 'Draft')
        ->assertSee('Active')
        ->assertPathIs('/employer/jobs');

    expect($job->refresh()->status)->toBe(JobStatus::Active)
        ->and($job->expires_at)->not->toBeNull();
});

test('an employer edits a job they already posted', function () {
    $job = Job::factory()->recycle($this->employer)->create([
        'title' => 'Staff Platform Engineer',
    ]);

    $this->actingAs($this->employer->user);

    $page = visit("/employer/jobs/{$job->id}/edit");

    assertPageEventuallyShows($page, 'Edit: Staff Platform Engineer')
        ->fill('title', 'Principal Platform Engineer')
        ->click('Save changes');

    assertPageEventuallyShows($page, 'Principal Platform Engineer')
        ->assertPathIs('/employer/jobs')
        ->assertDontSee('Staff Platform Engineer');

    expect($job->refresh()->title)->toBe('Principal Platform Engineer');
});

/**
 * The jobs list is the employer's route into every other job screen, so the two
 * links out of a row are worth asserting as links rather than as URLs.
 */
test('the jobs list links through to the applicants for a row', function () {
    Job::factory()->recycle($this->employer)->create([
        'title' => 'Staff Platform Engineer',
    ]);

    $this->actingAs($this->employer->user);

    $page = visit('/employer/jobs');

    // Addressed by href rather than by its label: the label is the applicant
    // count, and "0" is a string this page has more than one of.
    assertPageEventuallyShows($page, 'Staff Platform Engineer')
        ->click('a[href$="/applicants"]');

    assertPageEventuallyShows($page, 'Applicants for Staff Platform Engineer')
        ->assertSee('No applicants yet')
        ->assertNoJavaScriptErrors();
});
