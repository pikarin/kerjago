<?php

namespace App\Policies;

use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function __construct(private ResolveUnlockedProfileIds $resolveUnlockedProfileIds) {}

    /**
     * Determine whether the user can view the application.
     */
    public function view(User $user, Application $application): bool
    {
        return $this->isApplicant($user, $application) || $this->ownsJob($user, $application);
    }

    /**
     * Determine whether the user can change the application's status.
     */
    public function updateStatus(User $user, Application $application): bool
    {
        return $this->ownsJob($user, $application);
    }

    /**
     * Determine whether the user can download the submitted resume.
     *
     * Resumes are shared by the act of applying: only the applicant and the
     * owner of the job applied to may access the snapshot (ADR 0006). The
     * employer additionally needs an active Candidate Unlock — the CV carries
     * the name, email and phone the mask exists to withhold, so leaving it
     * downloadable would defeat the lock in one click (ADR 0013).
     */
    public function downloadResume(User $user, Application $application): bool
    {
        if ($this->isApplicant($user, $application)) {
            return true;
        }

        $employerProfile = $user->employerProfile;

        return $employerProfile !== null
            && $this->ownsJob($user, $application)
            && $this->resolveUnlockedProfileIds->has($employerProfile, $application->jobseeker_profile_id);
    }

    private function isApplicant(User $user, Application $application): bool
    {
        return $user->jobseekerProfile !== null
            && $application->jobseeker_profile_id === $user->jobseekerProfile->id;
    }

    private function ownsJob(User $user, Application $application): bool
    {
        return $user->employerProfile !== null
            && $application->job->employer_profile_id === $user->employerProfile->id;
    }
}
