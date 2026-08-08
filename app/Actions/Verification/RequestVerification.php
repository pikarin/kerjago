<?php

namespace App\Actions\Verification;

use App\Models\EmployerProfile;

/**
 * Record that a company has asked to be reviewed.
 *
 * A timestamp, not a state machine. `requested` / `under_review` columns need
 * someone to move companies between them, and with one flat staff role and
 * manual review a column nobody maintains starts lying inside a week.
 *
 * The first ask wins: clicking again does not restart the clock, because the
 * number staff sort on is "how long have they been waiting".
 */
class RequestVerification
{
    public function handle(EmployerProfile $employerProfile): EmployerProfile
    {
        if ($employerProfile->isVerified() || $employerProfile->verification_requested_at !== null) {
            return $employerProfile;
        }

        // Conditional on the column still being null, so a double-submitted
        // button cannot have both requests read null and both stamp — the later
        // write would win and move the company down a queue sorted on this very
        // timestamp.
        $claimed = EmployerProfile::query()
            ->whereKey($employerProfile->getKey())
            ->whereNull('verified_at')
            ->whereNull('verification_requested_at')
            ->update(['verification_requested_at' => now()]);

        if ($claimed > 0) {
            $employerProfile->refresh();
        }

        return $employerProfile;
    }
}
