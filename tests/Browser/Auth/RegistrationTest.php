<?php

use App\Enums\UserRole;
use App\Models\User;

/**
 * The role a new account gets is decided entirely in the browser: two buttons
 * write to a `ref`, which writes to a hidden input, which is what the server
 * finally reads. Nothing between the click and the request is server-side, so
 * tests/Feature/Auth/RegistrationTest.php — which posts a role directly — cannot
 * tell whether the picker is wired to that input at all.
 *
 * The two roles run as a dataset rather than as two near-identical tests: what
 * differs between them is the button, the dashboard it lands on, and the role
 * that ends up in the database, and each of those is a value.
 */
test('the role picker decides what kind of account is created', function (string $role, string $label, string $dashboardText) {
    $page = visit('/register');

    assertPageEventuallyShows($page, 'Create an account')
        ->click(sprintf('button:has-text("%s")', $label))
        ->assertAriaAttribute(sprintf('button:has-text("%s")', $label), 'pressed', 'true')
        ->fill('name', 'Rina Kartika')
        ->fill('email', 'rina@kerjago.test')
        ->fill('password', 'correct-horse-battery-staple')
        ->fill('password_confirmation', 'correct-horse-battery-staple')
        ->click('@register-user-button');

    // The dashboard each role lands on is the clearest evidence the hidden input
    // carried the choice: the two are different components, chosen server-side
    // from the role that was actually persisted.
    assertPageEventuallyShows($page, $dashboardText)
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticated();

    expect(User::query()->where('email', 'rina@kerjago.test')->sole()->role->value)->toBe($role);
})->with([
    'jobseeker' => [UserRole::Jobseeker->value, 'Jobseeker', 'Complete your profile'],
    'employer' => [UserRole::Employer->value, 'Employer', 'Set up your company profile'],
]);

/**
 * Jobseeker is pre-selected, so the picker has to say so before anything is
 * clicked — otherwise the default is invisible and the first option looks as
 * unchosen as the second.
 */
test('jobseeker is pre-selected on arrival', function () {
    $page = visit('/register');

    assertPageEventuallyShows($page, 'Create an account')
        ->assertAriaAttribute('button:has-text("Jobseeker")', 'pressed', 'true')
        ->assertAriaAttribute('button:has-text("Employer")', 'pressed', 'false');
});

test('a mismatched confirmation is reported without leaving the form', function () {
    $page = visit('/register');

    assertPageEventuallyShows($page, 'Create an account')
        ->fill('name', 'Rina Kartika')
        ->fill('email', 'rina@kerjago.test')
        ->fill('password', 'correct-horse-battery-staple')
        ->fill('password_confirmation', 'something-else-entirely')
        ->click('@register-user-button');

    assertPageEventuallyShows($page, 'The password field confirmation does not match.')
        ->assertPathIs('/register');

    $this->assertGuest();

    expect(User::query()->count())->toBe(0);
});

test('a registered account can be reached from the login link', function () {
    User::factory()->jobseeker()->create(['email' => 'rina@kerjago.test']);

    $page = visit('/register');

    assertPageEventuallyShows($page, 'Already have an account?')
        ->click('Log in');

    assertPageEventuallyShows($page, 'Log in to your account')
        ->assertPathIs('/login');
});
