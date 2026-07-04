<?php

use App\Enums\UserRole;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function (UserRole $role) {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => $role->value,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    expect(User::query()->firstOrFail()->role)->toBe($role);
})->with([
    'jobseeker' => UserRole::Jobseeker,
    'employer' => UserRole::Employer,
]);

test('registration requires a valid role', function (?string $role) {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => $role,
    ]);

    $response->assertSessionHasErrors('role');
    $this->assertGuest();
})->with([
    'missing' => null,
    'invalid' => 'admin',
]);
