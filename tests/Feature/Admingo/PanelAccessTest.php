<?php

use App\Admingo\Models\AppAuthenticator;
use App\Admingo\Models\StaffUser;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Admingo is gated three independent ways, and each test below fails on
 * exactly one of them so a regression names itself:
 *
 *   1. the StaffUser global scope, which makes a non-staff row unretrievable
 *   2. canAccessPanel() on StaffUser
 *   3. the EnsureStaffUser middleware, on page loads and Livewire updates
 *
 * See docs/adr/0011.
 */
test('guests are sent to the Admingo login page', function () {
    $this->get('/admingo')
        ->assertRedirect(route('filament.admingo.auth.login'));
});

test('a marketplace session alone does not grant panel access', function () {
    // The whole point of the separate guard: this user is fully authenticated
    // on the web guard and still has no session on admingo.
    $this->actingAs(User::factory()->staff()->create()) // web guard
        ->get('/admingo')
        ->assertRedirect(route('filament.admingo.auth.login'));
});

test('employers and jobseekers are rejected by the panel', function (string $role) {
    $user = User::factory()->create(['role' => $role]);

    // Forced onto the guard directly, bypassing the provider, so that the
    // middleware gate is what is under test rather than the global scope.
    $this->actingAs($user, 'admingo')
        ->get('/admingo')
        ->assertForbidden();
})->with([
    UserRole::Employer->value,
    UserRole::Jobseeker->value,
]);

test('staff who have enrolled in multi-factor authentication reach the panel', function () {
    $staff = StaffUser::factory()->create();
    AppAuthenticator::factory()->for($staff, 'staffUser')->create();

    $this->actingAs($staff, 'admingo')
        ->get('/admingo')
        ->assertOk();
});

test('staff must enrol in multi-factor authentication before using the panel', function () {
    // Filament's login runs none of Fortify's pipeline, so this redirect is
    // the only thing standing between a password and the panel.
    $staff = StaffUser::factory()->create();

    expect($staff->getAppAuthenticationSecret())->toBeNull();

    $this->actingAs($staff, 'admingo')
        ->get('/admingo')
        ->assertRedirect(route('filament.admingo.auth.multi-factor-authentication.set-up-required'));
});

test('the panel is not reachable through the marketplace guard even for staff', function () {
    $staff = StaffUser::factory()->create();
    AppAuthenticator::factory()->for($staff, 'staffUser')->create();

    $this->actingAs($staff, 'web')
        ->get('/admingo')
        ->assertRedirect(route('filament.admingo.auth.login'));
});

test('a demoted staff member becomes unretrievable by the Admingo guard', function () {
    $staff = StaffUser::factory()->create();

    expect(StaffUser::find($staff->id))->not->toBeNull();

    // Straight to the database, because the global scope also applies to
    // writes through the model.
    DB::table('users')->where('id', $staff->id)->update(['role' => UserRole::Employer->value]);

    expect(StaffUser::find($staff->id))->toBeNull()
        ->and(Auth::guard('admingo')->getProvider()->retrieveById($staff->id))->toBeNull();
});

test('the panel never provisions a non-staff user', function () {
    // make:filament-user fills only name, email and password against a
    // not-nullable users.role column.
    $staff = StaffUser::create([
        'name' => 'Internal Person',
        'email' => 'internal@kerjago.test',
        'password' => 'password',
        'role' => UserRole::Employer, // ignored: not fillable, and forced on create
    ]);

    expect($staff->role)->toBe(UserRole::Staff)
        ->and($staff->isStaff())->toBeTrue()
        ->and($staff->email_verified_at)->not->toBeNull();
});

test('multi-factor credentials round-trip through the Admingo-owned table', function () {
    $staff = StaffUser::factory()->create();

    $staff->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
    $staff->saveAppAuthenticationRecoveryCodes(['one', 'two']);

    expect($staff->refresh()->getAppAuthenticationSecret())->toBe('JBSWY3DPEHPK3PXP')
        ->and($staff->refresh()->getAppAuthenticationRecoveryCodes())->toBe(['one', 'two']);

    // Nothing Filament-shaped leaked onto the domain table.
    expect(DB::getSchemaBuilder()->hasColumn('users', 'app_authentication_secret'))->toBeFalse();

    // Disenrolment: Filament nulls each field in turn rather than deleting.
    $staff->saveAppAuthenticationSecret(null);
    $staff->saveAppAuthenticationRecoveryCodes(null);

    expect(AppAuthenticator::where('user_id', $staff->id)->exists())->toBeFalse();
});
