<?php

namespace App\Policies;

use App\Models\JobseekerProfile;
use App\Models\User;

class JobseekerProfilePolicy
{
    /**
     * Determine whether the user can view the jobseeker profile.
     *
     * Profiles are browsable by any employer; the resume file is not part
     * of the profile view (ADR 0006).
     */
    public function view(User $user, JobseekerProfile $jobseekerProfile): bool
    {
        return $user->isEmployer() || $jobseekerProfile->user_id === $user->id;
    }
}
