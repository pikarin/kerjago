<?php

namespace App\Support\Capabilities;

use App\Enums\CapabilityDenialReason;
use App\Enums\EmployerCapability;
use App\Models\EmployerProfile;

/**
 * The single place that decides what an employer may do.
 *
 * Two ordered stages, and the distinction between them is the part of this
 * design that is expensive to get wrong:
 *
 * - **Preconditions** are AND-ed. Every one must pass, whatever else grants the
 *   capability. Verification is today's only precondition.
 * - **Grants** are OR-ed. Any one suffices. Packages, trials and staff
 *   overrides will live here. There are none today.
 *
 * Verification is deliberately *not* a grant. If both stages were one OR-ed
 * list, the first package sale would become a way to buy past the trust check.
 *
 * No registry and no plugin points on purpose: the shape a package grant takes
 * is unknown until there is a package model, and an extension seam designed
 * around a guess is a migration either way. Adding one is two lines here.
 *
 * Nothing outside this class reads `verified_at` to answer a "may they?"
 * question. The Admingo queue and the employer's own status banner read it
 * directly, because those are genuinely *about* verification rather than about
 * a capability.
 */
final class EmployerCapabilities
{
    public function for(EmployerProfile $employerProfile, EmployerCapability $capability): CapabilityDecision
    {
        $denial = $this->precondition($employerProfile, $capability);

        if ($denial !== null) {
            return CapabilityDecision::deny($denial);
        }

        return $this->grant($employerProfile, $capability);
    }

    /**
     * Convenience for call sites that genuinely only need the yes/no — a policy
     * returning bool, say. Anything that renders differently on a denial should
     * take the whole decision and read the reason.
     */
    public function allows(EmployerProfile $employerProfile, EmployerCapability $capability): bool
    {
        return $this->for($employerProfile, $capability)->allowed;
    }

    /**
     * Every capability's decision, keyed by capability value — the shape the
     * frontend receives, so Vue branches on capabilities rather than on
     * verification and needs no sweep when a second gate arrives.
     *
     * @return array<string, array{allowed: bool, reason: string|null}>
     */
    public function map(EmployerProfile $employerProfile): array
    {
        $map = [];

        foreach (EmployerCapability::cases() as $capability) {
            $map[$capability->value] = $this->for($employerProfile, $capability)->toArray();
        }

        return $map;
    }

    /**
     * The rules no grant can override. Returns the reason the capability is
     * withheld, or null when every precondition passes.
     *
     * Every capability requires verification today. A future capability that
     * should stay open to unverified companies declares that here, rather than
     * at the surface that renders it.
     */
    private function precondition(EmployerProfile $employerProfile, EmployerCapability $capability): ?CapabilityDenialReason
    {
        if (! $employerProfile->isVerified()) {
            return CapabilityDenialReason::VerificationRequired;
        }

        return null;
    }

    /**
     * The sources that confer a capability. Any one is enough.
     *
     * Empty today: clearing the preconditions is all it takes. When packages
     * arrive this becomes "does any active package include this capability?",
     * and everything above stays as it is.
     */
    private function grant(EmployerProfile $employerProfile, EmployerCapability $capability): CapabilityDecision
    {
        return CapabilityDecision::allow();
    }
}
