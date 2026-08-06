<?php

namespace App\Policies;

use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Models\JobseekerProfile;
use App\Models\User;

class JobseekerProfilePolicy
{
    public function __construct(private ResolveUnlockedProfileIds $resolveUnlockedProfileIds) {}

    /**
     * Determine whether the user can view the jobseeker profile.
     *
     * Profiles are browsable by any employer; the resume file is not part
     * of the profile view (ADR 0006), and a Locked Candidate's identity is
     * masked by the Resource rather than by refusing the page.
     */
    public function view(User $user, JobseekerProfile $jobseekerProfile): bool
    {
        return $user->isEmployer() || $jobseekerProfile->user_id === $user->id;
    }

    /**
     * Determine whether the user may see the candidate's real name, email and
     * phone numbers.
     *
     * The seam ADR 0007 reserved for quota gating. Employers need an active
     * Candidate Unlock; the jobseeker themself always qualifies, and staff hold
     * no employer profile so they are never gated here (ADR 0013).
     */
    public function viewContact(User $user, JobseekerProfile $jobseekerProfile): bool
    {
        if ($jobseekerProfile->user_id === $user->id || $user->isStaff()) {
            return true;
        }

        if ($user->employerProfile === null) {
            return false;
        }

        return $this->resolveUnlockedProfileIds->has($user->employerProfile, $jobseekerProfile->id);
    }
}
