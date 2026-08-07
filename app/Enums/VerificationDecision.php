<?php

namespace App\Enums;

/**
 * What one Verification Event recorded. Verification is binary, so these are
 * the only two things that can ever have happened.
 */
enum VerificationDecision: string
{
    case Verified = 'verified';
    case Unverified = 'unverified';
}
