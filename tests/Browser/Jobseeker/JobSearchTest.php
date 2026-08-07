<?php

use App\Enums\WorkArrangement;

/**
 * The job board's whole filter layer lives in the client: a 300ms debounce, a
 * reactive form object, a sort that flips itself between newest and relevance,
 * and facet checkboxes that push a partial Inertia visit. tests/Feature does its
 * asserting by handing the controller a query string, so none of that machinery
 * is exercised there — only the query it eventually produces.
 *
 * Both fixtures spell out every field the keyword search reads — title,
 * description, city — because activeJob()'s defaults are shared and the second
 * job has to be unreachable by the word the first is found with.
 */
beforeEach(function () {
    activeJob([
        'title' => 'Senior Laravel Developer',
        'work_arrangement' => WorkArrangement::Remote,
    ]);

    activeJob([
        'title' => 'Product Designer',
        'description' => 'Shape the interface a million people use every week.',
        'location_city' => 'Bandung',
        'work_arrangement' => WorkArrangement::Onsite,
    ]);
});

test('typing a keyword narrows the board', function () {
    $page = visit('/jobs');

    assertPageEventuallyShows($page, 'Senior Laravel Developer')
        ->assertSee('Product Designer')
        ->fill('[type="search"]', 'Laravel');

    assertPageEventuallyHides($page, 'Product Designer')
        ->assertSee('Senior Laravel Developer')
        ->assertQueryStringHas('q', 'Laravel');
});

/**
 * Searching switches the sort to relevance on the empty → typed transition, and
 * that option only exists in the list while a query is present. Both halves are
 * client-only: the server never sends the option list.
 */
test('the relevance sort appears only once there is a query', function () {
    $page = visit('/jobs');

    assertPageEventuallyShows($page, 'Newest')
        ->assertDontSee('Relevance')
        ->fill('[type="search"]', 'Laravel');

    assertPageEventuallyShows($page, 'Relevance')
        ->assertQueryStringHas('sort', 'relevance');
});

test('a facet checkbox filters the board', function () {
    $page = visit('/jobs');

    assertPageEventuallyShows($page, 'Product Designer')
        ->click('[for="facet-Work arrangement-remote"]');

    // The parameter itself is not asserted: the facet arrives as
    // `work_arrangement[0]`, and assertQueryStringHas matches the raw key, so
    // the encoded brackets never line up. What the filter did to the list is
    // the claim worth making anyway.
    assertPageEventuallyHides($page, 'Product Designer')
        ->assertSee('Senior Laravel Developer');
});

/**
 * Clear filters is a pure client action — it resets the reactive form, and the
 * debounced watcher is what turns that into a fresh visit. Worth its own test
 * because a reset that misses a field leaves a filter silently applied.
 */
test('clearing the filters brings every job back', function () {
    $page = visit('/jobs');

    assertPageEventuallyShows($page, 'Product Designer')
        ->click('[for="facet-Work arrangement-remote"]');

    assertPageEventuallyHides($page, 'Product Designer')
        ->fill('[type="search"]', 'Laravel');

    assertPageEventuallyShows($page, 'Relevance')
        ->click('Clear filters');

    assertPageEventuallyShows($page, 'Product Designer')
        ->assertSee('Senior Laravel Developer')
        ->assertQueryStringMissing('q')
        ->assertQueryStringMissing('work_arrangement');
});

test('a search that matches nothing shows the empty state', function () {
    $page = visit('/jobs');

    assertPageEventuallyShows($page, 'Senior Laravel Developer')
        ->fill('[type="search"]', 'Underwater Basket Weaver');

    assertPageEventuallyShows($page, 'No jobs found')
        ->assertDontSee('Senior Laravel Developer')
        ->assertNoJavaScriptErrors();
});

test('a job card opens the job it names', function () {
    $page = visit('/jobs');

    assertPageEventuallyShows($page, 'Senior Laravel Developer')
        ->click('Senior Laravel Developer');

    assertPageEventuallyShows($page, 'Log in as a jobseeker to apply for this role.')
        ->assertPathBeginsWith('/jobs/')
        ->assertNoJavaScriptErrors();
});
