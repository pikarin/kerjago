<?php

use App\Actions\Applications\ApplyToJob;
use App\Enums\Availability;
use App\Models\CandidateUnlock;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;

/**
 * Talent search carries the same debounced client-side filter machinery as the
 * public job board, plus a "More filters" collapsible that hides half the facets
 * until it is opened — so a filter can be present in the props and unreachable
 * on the page, which is the kind of gap only a browser test closes.
 *
 * The keyword search reads name, both titles, company, summary *and* skills, so
 * the fixtures below pin every one of them. The factory's skill pool includes
 * "Laravel", which it hands out at random — leaving the designer matching a
 * search for "Laravel" in roughly half of all runs.
 */
beforeEach(function () {
    $this->employer = EmployerProfile::factory()->verified()->create();

    JobseekerProfile::factory()->create([
        'full_name' => 'Rina Kartika',
        'preferred_job_title' => 'Laravel Developer',
        'current_title' => 'Backend Engineer',
        'current_company' => 'Warung Digital',
        'summary' => 'Ten years of server-side work across Southeast Asia.',
        'skills' => ['Laravel', 'PHP', 'PostgreSQL'],
        'availability' => Availability::Immediately,
        'city' => 'Jakarta',
        'country' => 'ID',
    ]);

    JobseekerProfile::factory()->create([
        'full_name' => 'Budi Santoso',
        'preferred_job_title' => 'Product Designer',
        'current_title' => 'Product Designer',
        'current_company' => 'Studio Sembilan',
        'summary' => 'Interface and brand work for consumer apps.',
        'skills' => ['Figma', 'Prototyping'],
        'availability' => Availability::TwoMonthsPlus,
        'city' => 'Bandung',
        'country' => 'ID',
    ]);
});

test('an employer narrows the candidate list by keyword', function () {
    $this->actingAs($this->employer->user);

    $page = visit('/employer/talent');

    assertPageEventuallyShows($page, 'Rina K.')
        ->assertSee('Budi S.')
        ->fill('[type="search"]', 'Laravel');

    assertPageEventuallyHides($page, 'Budi S.')
        ->assertSee('Rina K.')
        ->assertQueryStringHas('q', 'Laravel');
});

test('an availability facet narrows the candidate list', function () {
    $this->actingAs($this->employer->user);

    $page = visit('/employer/talent');

    assertPageEventuallyShows($page, 'Budi S.')
        ->click('[for="facet-Availability-immediately"]');

    assertPageEventuallyHides($page, 'Budi S.')
        ->assertSee('Rina K.');
});

/**
 * The secondary facets live behind a Collapsible, so they are absent from the
 * DOM until the trigger is clicked. Nothing server-side can tell you that.
 */
test('the extra facets stay hidden until More filters is opened', function () {
    $this->actingAs($this->employer->user);

    $page = visit('/employer/talent');

    assertPageEventuallyShows($page, 'More filters')
        ->assertDontSee('Preferred country')
        ->click('More filters');

    assertPageEventuallyShows($page, 'Preferred country')
        ->assertSee('Languages')
        ->assertSee('Gender')
        ->assertNoJavaScriptErrors();
});

test('a search that matches nobody shows the empty state', function () {
    $this->actingAs($this->employer->user);

    $page = visit('/employer/talent');

    assertPageEventuallyShows($page, 'Rina K.')
        ->fill('[type="search"]', 'Underwater Basket Weaver');

    assertPageEventuallyShows($page, 'No candidates found')
        ->assertDontSee('Rina K.')
        ->assertNoJavaScriptErrors();
});

/**
 * Every candidate an employer has not unlocked arrives masked, search results
 * included — the list is the cheapest place to leak a surname, so it is asserted
 * here as well as on the profile the card opens.
 */
test('an unseen candidate is masked in the list and on their profile', function () {
    $profile = JobseekerProfile::query()->where('full_name', 'Rina Kartika')->sole();

    $this->actingAs($this->employer->user);

    $page = visit('/employer/talent');

    // Clicked by href, not by name. The card's title element holds the masked
    // name *and* the locked badge, so no element's text is exactly "Rina K." —
    // and the plugin's text fallback is an exact match that would find nothing
    // and then block on it rather than fail.
    assertPageEventuallyShows($page, 'Rina K.')
        ->assertDontSee('Kartika')
        ->assertVisible('@locked-badge')
        ->click(sprintf('a[href$="/employer/talent/%s"]', $profile->id));

    assertPageEventuallyShows($page, 'This candidate is locked.')
        ->assertDontSee('Kartika')
        ->assertVisible('@locked-badge')
        ->assertNoJavaScriptErrors();
});

test('a candidate who applied to one of the jobs is shown in full', function () {
    $profile = JobseekerProfile::query()->where('full_name', 'Rina Kartika')->sole();
    $job = activeJob(['employer_profile_id' => $this->employer->id]);

    app(ApplyToJob::class)->handle($profile, $job);

    expect(CandidateUnlock::query()->count())->toBe(1);

    $this->actingAs($this->employer->user);

    $page = visit("/employer/talent/{$profile->id}");

    assertPageEventuallyShows($page, 'Rina Kartika')
        ->assertMissing('@locked-badge')
        ->assertDontSee('This candidate is locked.');
});
