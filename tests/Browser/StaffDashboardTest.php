<?php

use App\Models\User;

/**
 * A staff account on the marketplace side is a third thing beside a jobseeker
 * and an employer: it holds neither profile, so it gets its own dashboard
 * rather than being pushed into a profile form the way an incomplete jobseeker
 * or employer would be.
 *
 * This is the marketplace, not Admingo — the panel's own guard is covered by
 * tests/Feature/Admingo/PanelAccessTest.php, which asserts every direction of
 * it over plain HTTP and so does not need a browser.
 */
test('a staff user on the marketplace gets the internal dashboard', function () {
    $this->actingAs(User::factory()->staff()->create());

    $page = visit('/dashboard');

    assertPageEventuallyShows($page, 'Internal')
        ->assertSee('No internal tools yet')
        ->assertDontSee('Complete your profile')
        ->assertDontSee('Set up your company profile')
        ->assertNoJavaScriptErrors();
});
