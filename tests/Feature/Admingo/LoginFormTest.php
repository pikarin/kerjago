<?php

use App\Admingo\Models\StaffUser;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;

use function Pest\Livewire\livewire;

/**
 * The recovery-code half of the multi-factor challenge, tested at the Livewire
 * layer rather than in tests/Browser/Admingo/SignInTest.php.
 *
 * Filament marks the recovery field `live(onBlur: true)`, so in a real browser
 * the value only reaches the server on a blur round-trip that races the submit
 * click — the browser version of this test failed roughly two runs in three,
 * and every way of waiting for that round-trip is a timer rather than a signal.
 * What is actually under test here is state, not rendering: that a valid code
 * signs a staff member in, and that it is spent afterwards.
 *
 * Filament drives both steps through the same `authenticate()` call: the first
 * validates credentials and raises the challenge, the second validates it.
 */
beforeEach(function () {
    Filament::setCurrentPanel('admingo');
});

/**
 * @param  array<string>  $codes
 */
function enrolledStaffWithRecoveryCodes(array $codes): StaffUser
{
    $staff = StaffUser::factory()->create(['email' => 'staff@kerjago.test']);

    $provider = AppAuthentication::make();
    $provider->saveSecret($staff, $provider->generateSecret());
    $provider->saveRecoveryCodes($staff->refresh(), $codes);

    return $staff->refresh();
}

test('a recovery code signs staff in and is spent in the process', function () {
    $staff = enrolledStaffWithRecoveryCodes(['aaaa-bbbb', 'cccc-dddd']);

    livewire(Login::class)
        ->fillForm(['email' => 'staff@kerjago.test', 'password' => 'password'])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->fillForm(['multiFactor' => ['app' => ['useRecoveryCode' => true, 'recoveryCode' => 'aaaa-bbbb']]])
        ->call('authenticate')
        ->assertHasNoFormErrors();

    expect(auth('admingo')->check())->toBeTrue()
        ->and(AppAuthentication::make()->getRecoveryCodes($staff->refresh()))->toHaveCount(1);
});

test('an unknown recovery code neither signs staff in nor spends a real one', function () {
    $staff = enrolledStaffWithRecoveryCodes(['aaaa-bbbb', 'cccc-dddd']);

    livewire(Login::class)
        ->fillForm(['email' => 'staff@kerjago.test', 'password' => 'password'])
        ->call('authenticate')
        ->fillForm(['multiFactor' => ['app' => ['useRecoveryCode' => true, 'recoveryCode' => 'not-a-code']]])
        ->call('authenticate')
        ->assertHasFormErrors();

    expect(auth('admingo')->check())->toBeFalse()
        ->and(AppAuthentication::make()->getRecoveryCodes($staff->refresh()))->toHaveCount(2);
});
