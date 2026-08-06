<?php

namespace App\Actions\Unlocks;

use App\Actions\Applications\ApplyToJob;
use App\Models\Job;

/**
 * How many of a job's ten auto-unlock slots are gone, for the employer-facing
 * "N of 10 used" badge.
 *
 * Counts applications rather than unlock rows, matching how the slot is actually
 * spent: an applicant the employer had already unlocked elsewhere collapses into
 * their existing row but still uses their place in the first ten.
 */
class CountJobUnlocksUsed
{
    public function handle(Job $job): int
    {
        return min($job->applications()->count(), ApplyToJob::AUTO_UNLOCK_QUOTA);
    }
}
