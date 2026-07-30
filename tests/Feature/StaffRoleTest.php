<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Every route carrying `profile.complete` also carries a `role:` guard, so a
 * staff user can never reach one in the real route table — the role check
 * rejects them first. These tests register a throwaway route to exercise the
 * completeness gate on its own.
 */
function gatedTestRoute(string ...$middleware): void
{
    Route::middleware(['web', 'auth', ...$middleware])
        ->get('/__test/gated', fn () => response('ok'));
}

test('staff pass through the profile completeness gate', function () {
    gatedTestRoute('profile.complete');

    $this->actingAs(User::factory()->staff()->create())
        ->get('/__test/gated')
        ->assertOk()
        ->assertSee('ok');
});

// Negative control. Without this, the test above could pass simply because
// the middleware never ran on the throwaway route.
test('jobseekers without a profile are stopped by the same gate', function () {
    gatedTestRoute('profile.complete');

    $this->actingAs(User::factory()->jobseeker()->create())
        ->get('/__test/gated')
        ->assertRedirect(route('jobseeker.profile.edit'));
});

test('the role middleware admits staff and rejects other roles', function () {
    gatedTestRoute('role:staff');

    $this->actingAs(User::factory()->staff()->create())
        ->get('/__test/gated')
        ->assertOk();

    $this->actingAs(User::factory()->employer()->create())
        ->get('/__test/gated')
        ->assertForbidden();
});

test('staff hold neither profile type', function () {
    $staff = User::factory()->staff()->create();

    expect($staff->employerProfile)->toBeNull()
        ->and($staff->jobseekerProfile)->toBeNull()
        ->and($staff->isStaff())->toBeTrue()
        ->and($staff->isEmployer())->toBeFalse()
        ->and($staff->isJobseeker())->toBeFalse();
});
