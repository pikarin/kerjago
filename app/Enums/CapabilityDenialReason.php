<?php

namespace App\Enums;

/**
 * Why an Employer Capability was withheld.
 *
 * Carried on every denial because the three gated surfaces treat a denial
 * three different ways — a shallow search here, a disabled composer there — and
 * a bare boolean would make each of them re-derive the reason. It is also what
 * picks the copy: the same Vue component says "verify your company" today and
 * "upgrade your plan" once packages exist.
 */
enum CapabilityDenialReason: string
{
    case VerificationRequired = 'verification_required';
}
