<?php

use Filament\Auth\MultiFactor\App\AppAuthentication;

/**
 * Admingo is the one part of the application whose front end is not ours: the
 * panel's JavaScript ships with Filament and is republished by
 * `filament:upgrade` on every install. These are the cheap alarm for that —
 * they assert the panel's own assets actually boot, which no Feature test can
 * see because those never run JavaScript.
 */
test('the unauthenticated panel surfaces boot without console noise', function () {
    $pages = visit(['/admingo', '/admingo/login']);

    $pages->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});

test('the dashboard and the profile page boot without console noise', function () {
    [$staff, $secret] = enrolStaffUser(['email' => 'staff@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'staff@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'Verify your identity')
        ->fill('[id="multiFactorChallengeForm.app.code"]', AppAuthentication::make()->getCurrentCode($staff, $secret))
        ->submit();

    assertPageEventuallyShows($page, 'Dashboard')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();

    // The same browser context, so the session established above carries over.
    assertPageEventuallyShows(visit('/admingo/profile'), 'Authenticator app')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
});
