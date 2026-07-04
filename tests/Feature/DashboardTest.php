<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('jobseekers see the jobseeker dashboard', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Jobseeker'));
});

test('employers see the employer dashboard', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('dashboard/Employer'));
});
