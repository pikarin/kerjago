<?php

use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;

test('employer with a company profile can browse jobseeker profiles', function () {
    JobseekerProfile::factory(3)->create();

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('employer/talent/Index')
            ->has('profiles.data', 3)
        );
});

test('employer without a company profile is redirected to profile setup', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('employer.talent.index'))
        ->assertRedirect(route('employer.profile.edit', absolute: false));
});

test('jobseeker cannot access talent search', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('employer.talent.index'))
        ->assertForbidden();
});

test('guests are redirected to login from talent search', function () {
    $this->get(route('employer.talent.index'))->assertRedirect(route('login', absolute: false));
});

test('talent can be searched by keyword across name, title, and skills', function () {
    JobseekerProfile::factory()->create(['full_name' => 'Andi Wijaya', 'current_title' => 'Chef', 'skills' => ['Cooking']]);
    JobseekerProfile::factory()->create(['full_name' => 'Budi Santoso', 'current_title' => 'Laravel Developer', 'skills' => ['PHP']]);
    JobseekerProfile::factory()->create(['full_name' => 'Citra Dewi', 'current_title' => 'Designer', 'skills' => ['Laravel', 'Vue.js']]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['q' => 'laravel']))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 2));
});

test('talent can be filtered by country and experience', function () {
    JobseekerProfile::factory()->create(['country' => 'ID', 'experience_years' => 2]);
    JobseekerProfile::factory()->create(['country' => 'ID', 'experience_years' => 8]);
    JobseekerProfile::factory()->create(['country' => 'VN', 'experience_years' => 10]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['country' => 'ID', 'experience_min' => 5]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 1));
});

test('employer can view a candidate profile', function () {
    $profile = JobseekerProfile::factory()->create();

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.show', $profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('employer/talent/Show')
            ->where('profile.id', $profile->id)
        );
});
