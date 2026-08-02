<?php

use App\Admingo\Models\AppAuthenticator;
use App\Admingo\Models\StaffUser;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;

/**
 * End-to-end cover for the one part of Admingo that tests/Feature/Admingo
 * cannot reach: the login itself.
 *
 * Those tests all arrive through actingAs(), which mints a session directly and
 * so skips the form, the credential check and the multi-factor challenge. That
 * matters more here than it would on the marketplace side, because Filament
 * authenticates on its own page and runs none of Fortify's pipeline — the
 * panel's `isRequired: true` multi-factor configuration is the only thing
 * standing between a staff password and the panel. See docs/adr/0011.
 *
 * Filament renders the challenge on the login page itself, so a session only
 * exists once a code is accepted. Fields are addressed by explicit `[id="…"]`
 * selectors rather than by label: Filament's state paths contain a period,
 * which the browser plugin would read as a CSS class selector, match nothing,
 * and then wait on until the run is killed.
 */
test('staff sign in with a code from their authenticator app', function () {
    [$staff, $secret] = enrolStaffUser(['email' => 'staff@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'staff@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'Verify your identity')
        ->fill('[id="multiFactorChallengeForm.app.code"]', AppAuthentication::make()->getCurrentCode($staff, $secret))
        ->submit();

    assertPageEventuallyShows($page, 'Dashboard')
        ->assertPathIs('/admingo')
        ->assertNoJavaScriptErrors();
});

test('a wrong password never reaches the challenge', function () {
    enrolStaffUser(['email' => 'staff@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'staff@kerjago.test')
        ->fill('[id="form.password"]', 'not-the-password')
        ->submit();

    assertPageEventuallyShows($page, 'These credentials do not match our records.')
        ->assertDontSee('Verify your identity')
        ->assertPathIs('/admingo/login');
});

/**
 * The outermost gate, and the reason the panel has its own guard: StaffScope
 * makes a non-staff row unretrievable, so these are correct credentials that
 * still cannot describe a panel user.
 */
test('correct marketplace credentials are not Admingo credentials', function (string $role) {
    User::factory()->create([
        'email' => 'outsider@kerjago.test',
        'role' => $role,
    ]);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'outsider@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'These credentials do not match our records.')
        ->assertPathIs('/admingo/login');
})->with([
    UserRole::Employer->value,
    UserRole::Jobseeker->value,
]);

test('a password alone does not get past the challenge', function () {
    enrolStaffUser(['email' => 'staff@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'staff@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'Verify your identity')
        ->fill('[id="multiFactorChallengeForm.app.code"]', '000000')
        ->submit();

    assertPageEventuallyShows($page, 'The code you entered is invalid.')
        ->assertDontSee('Dashboard');
});

/**
 * Staff who have not enrolled are held on the set-up screen rather than let in,
 * which is what `isRequired: true` buys. Relaxing it would turn this into a
 * password-only panel.
 */
test('staff who have not enrolled cannot get in at all', function () {
    StaffUser::factory()->create(['email' => 'new-hire@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'new-hire@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'Set up')
        ->assertDontSee('Dashboard');

    expect(AppAuthenticator::query()->count())->toBe(0);
});

/**
 * The recovery link is asserted here only as far as the form it reveals.
 * Signing in with a code is covered by tests/Feature/Admingo/LoginFormTest.php
 * instead: the field is `live(onBlur: true)`, so in a browser the value reaches
 * the server on a blur round-trip that races the submit click, and every way of
 * waiting for that round-trip is a timer rather than a signal.
 */
test('the challenge offers a recovery code as a way through', function () {
    enrolStaffUser(['email' => 'staff@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'staff@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'Verify your identity')
        ->click('Use a recovery code instead');

    assertPageEventuallyShows($page, 'Or, enter a recovery code')
        ->assertNoJavaScriptErrors();
});

test('signing out drops the panel session', function () {
    [$staff, $secret] = enrolStaffUser(['email' => 'staff@kerjago.test']);

    $page = visit('/admingo/login')
        ->fill('[id="form.email"]', 'staff@kerjago.test')
        ->fill('[id="form.password"]', 'password')
        ->submit();

    assertPageEventuallyShows($page, 'Verify your identity')
        ->fill('[id="multiFactorChallengeForm.app.code"]', AppAuthentication::make()->getCurrentCode($staff, $secret))
        ->submit();

    // The dashboard's AccountWidget and the topbar user menu each render a
    // logout form, and the widget renders both an icon-only and a labelled
    // button, so the selector has to name exactly one: an ambiguous locator is
    // a strict-mode error, and a hidden one would be waited on rather than
    // reported.
    assertPageEventuallyShows($page, 'Dashboard')
        ->click('#fi-main-content form[action$="/admingo/logout"] button:has-text("Sign out")');

    assertPageEventuallyShows($page, 'Sign in')
        ->assertPathIs('/admingo/login');

    assertPageEventuallyShows(visit('/admingo'), 'Sign in')
        ->assertPathIs('/admingo/login');
});
