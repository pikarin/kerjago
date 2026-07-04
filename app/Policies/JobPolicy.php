<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /**
     * Determine whether the user can post new jobs.
     */
    public function create(User $user): bool
    {
        return $user->isEmployer() && $user->employerProfile !== null;
    }

    /**
     * Determine whether the user can update the job.
     */
    public function update(User $user, Job $job): bool
    {
        return $this->ownsJob($user, $job);
    }

    /**
     * Determine whether the user can view the job's applicants.
     */
    public function viewApplicants(User $user, Job $job): bool
    {
        return $this->ownsJob($user, $job);
    }

    private function ownsJob(User $user, Job $job): bool
    {
        return $user->employerProfile !== null
            && $job->employer_profile_id === $user->employerProfile->id;
    }
}
