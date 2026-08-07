<?php

use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

/**
 * The marketplace login and logout, driven through the real forms.
 *
 * Every other browser test reaches its page through actingAs(), which mints a
 * session directly and so never touches the login form, the CSRF token or the
 * redirect that follows. This file is what covers that ground once, so the rest
 * do not have to pay for a form submission they are not testing.
 *
 * tests/Feature/Auth/AuthenticationTest.php asserts the same route from the
 * server side; what it cannot see is that the form posts what it collected.
 */
test('a jobseeker signs in through the form and lands on their dashboard', function () {
    Event::fake([Login::class]);

    $profile = JobseekerProfile::factory()->create();
    $profile->user->update(['email' => 'rina@kerjago.test']);

    $page = visit('/login');

    assertPageEventuallyShows($page, 'Log in to your account')
        ->fill('email', 'rina@kerjago.test')
        ->fill('password', 'password')
        ->click('@login-button');

    assertPageEventuallyShows($page, 'Overview')
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    $this->assertAuthenticatedAs($profile->user);

    Event::assertDispatched(Login::class);
});

test('an employer signing in lands on the employer dashboard', function () {
    $employer = EmployerProfile::factory()->verified()->create();
    $employer->user->update(['email' => 'budi@kerjago.test']);

    $page = visit('/login');

    assertPageEventuallyShows($page, 'Log in to your account')
        ->fill('email', 'budi@kerjago.test')
        ->fill('password', 'password')
        ->click('@login-button');

    assertPageEventuallyShows($page, 'Overview')
        ->assertSee('Recent jobs')
        ->assertPathIs('/dashboard');

    $this->assertAuthenticatedAs($employer->user);
});

test('a wrong password is reported on the form and mints no session', function () {
    Event::fake([Failed::class, Login::class]);

    User::factory()->jobseeker()->create(['email' => 'rina@kerjago.test']);

    $page = visit('/login');

    assertPageEventuallyShows($page, 'Log in to your account')
        ->fill('email', 'rina@kerjago.test')
        ->fill('password', 'not-the-password')
        ->click('@login-button');

    assertPageEventuallyShows($page, 'These credentials do not match our records.')
        ->assertPathIs('/login');

    $this->assertGuest();

    Event::assertDispatched(Failed::class);
    Event::assertNotDispatched(Login::class);
});

test('the login page offers a way to the password reset form', function () {
    $page = visit('/login');

    assertPageEventuallyShows($page, 'Log in to your account')
        ->click('Forgot your password?');

    assertPageEventuallyShows($page, 'Email password reset link')
        ->assertPathIs('/forgot-password')
        ->assertNoJavaScriptErrors();
});

test('a signed-in user can log out again', function () {
    $this->actingAs(User::factory()->jobseeker()->create());

    $page = visit('/dashboard');

    assertPageEventuallyShows($page, 'Complete your profile')
        ->click('@sidebar-menu-button');

    // Fortify drops a logged-out user on the marketing home page, not the login
    // form — so the sign-in link being back in the header is what says the
    // session ended.
    assertPageEventuallyShows($page, 'Log out')
        ->click('@logout-button');

    assertPageEventuallyShows($page, 'Your next job in SEA')
        ->assertPathIs('/')
        ->assertSeeLink('Log in');

    $this->assertGuest();

    // The session is really gone, not merely navigated away from.
    assertPageEventuallyShows(visit('/dashboard'), 'Log in to your account')
        ->assertPathIs('/login');
});
