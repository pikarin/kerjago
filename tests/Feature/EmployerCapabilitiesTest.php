<?php

use App\Enums\CapabilityDenialReason;
use App\Enums\EmployerCapability;
use App\Models\EmployerProfile;
use App\Support\Capabilities\EmployerCapabilities;

/**
 * Profiles are built with make(), not create(): the resolver reads state off
 * the model and touches nothing else, and keeping it that way is part of what
 * makes it cheap enough to call once per capability per page.
 */
function capabilities(): EmployerCapabilities
{
    return new EmployerCapabilities;
}

it('allows every capability to a verified employer', function (EmployerCapability $capability) {
    $profile = EmployerProfile::factory()->verified()->make();

    $decision = capabilities()->for($profile, $capability);

    expect($decision->allowed)->toBeTrue()
        ->and($decision->reason)->toBeNull();
})->with(EmployerCapability::cases());

it('withholds every capability from an unverified employer, with the reason', function (EmployerCapability $capability) {
    $profile = EmployerProfile::factory()->unverified()->make();

    $decision = capabilities()->for($profile, $capability);

    expect($decision->allowed)->toBeFalse()
        ->and($decision->isDenied())->toBeTrue()
        ->and($decision->reason)->toBe(CapabilityDenialReason::VerificationRequired);
})->with(EmployerCapability::cases());

it('reduces to a boolean for call sites that only need one', function () {
    $verified = EmployerProfile::factory()->verified()->make();
    $unverified = EmployerProfile::factory()->unverified()->make();

    expect(capabilities()->allows($verified, EmployerCapability::ParticipateInChat))->toBeTrue()
        ->and(capabilities()->allows($unverified, EmployerCapability::ParticipateInChat))->toBeFalse();
});

it('serialises every capability for the frontend, reason included', function () {
    $map = capabilities()->map(EmployerProfile::factory()->unverified()->make());

    expect($map)->toHaveCount(count(EmployerCapability::cases()))
        ->and($map[EmployerCapability::BrowseTalentInFull->value])->toBe([
            'allowed' => false,
            'reason' => 'verification_required',
        ]);
});

it('serialises an allowed decision with a null reason', function () {
    $map = capabilities()->map(EmployerProfile::factory()->verified()->make());

    expect($map[EmployerCapability::PublishJob->value])->toBe([
        'allowed' => true,
        'reason' => null,
    ]);
});
