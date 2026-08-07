/**
 * What the signed-in employer may do.
 *
 * The frontend is handed capabilities rather than a verification flag on
 * purpose. Branching on `verification.verified` anywhere outside the
 * company page would be one more place to find when a second gate arrives — and
 * the one place a missed check fails open, because the button renders and the
 * user clicks it.
 */
export type EmployerCapabilityName =
    | 'publish_job'
    | 'browse_talent_in_full'
    | 'participate_in_chat';

/**
 * Why a capability is withheld. Picks the copy: "verify your company" today,
 * "upgrade your plan" once packages exist — same component either way.
 */
export type CapabilityDenialReason = 'verification_required';

export type CapabilityDecision = {
    allowed: boolean;
    reason: CapabilityDenialReason | null;
};

export type EmployerCapabilities = Record<
    EmployerCapabilityName,
    CapabilityDecision
>;

/**
 * Verification state itself, for the two surfaces that are genuinely about
 * verification: the company-page banner and the request button. Everything else
 * asks the capability map.
 */
export type VerificationState = {
    verified: boolean;
    requested_at: string | null;
};
