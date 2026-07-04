<?php

use App\Models\EmployerProfile;
use App\Models\User;

test('employer can view the company profile form', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('employer.profile.edit'))
        ->assertOk();
});

test('jobseeker cannot access the company profile form', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('employer.profile.edit'))
        ->assertForbidden();
});

test('employer can create a company profile', function () {
    $user = User::factory()->employer()->create();

    $this->actingAs($user)->put(route('employer.profile.update'), [
        'company_name' => 'Kerjago Co',
        'industry' => 'Technology',
        'country' => 'SG',
        'city' => 'Singapore',
        'website' => 'https://kerjago.test',
    ])->assertRedirect(route('employer.profile.edit', absolute: false));

    $profile = EmployerProfile::query()->whereBelongsTo($user)->firstOrFail();

    expect($profile->company_name)->toBe('Kerjago Co')
        ->and($profile->country)->toBe('SG');
});

test('employer can update an existing company profile', function () {
    $user = User::factory()->employer()->create();
    EmployerProfile::factory()->for($user)->create();

    $this->actingAs($user)->put(route('employer.profile.update'), [
        'company_name' => 'Renamed Co',
        'industry' => 'Finance',
        'country' => 'MY',
        'city' => 'Kuala Lumpur',
        'website' => null,
    ])->assertRedirect(route('employer.profile.edit', absolute: false));

    expect(EmployerProfile::query()->whereBelongsTo($user)->count())->toBe(1)
        ->and($user->refresh()->employerProfile?->company_name)->toBe('Renamed Co');
});
