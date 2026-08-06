<?php

namespace App\Support\Chat;

use App\Enums\ConversationKind;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;

/**
 * The unique key that makes cold outreach at-most-one per employer/jobseeker
 * pair.
 *
 * Composed here rather than inline because three places need the same string —
 * StartColdOutreach writes it, IssueCandidateUnlock restores by it, and
 * ExpireCandidateUnlocks revokes by it. A mismatch between the last two would
 * strand a thread revoked forever, so the format lives in one place.
 */
class ColdOutreachKey
{
    public static function forUsers(string $employerUserId, string $jobseekerUserId): string
    {
        return sprintf(
            '%s:%s:%s',
            ConversationKind::ColdOutreach->value,
            $employerUserId,
            $jobseekerUserId,
        );
    }

    public static function for(EmployerProfile $employerProfile, JobseekerProfile $jobseekerProfile): string
    {
        return self::forUsers($employerProfile->user_id, $jobseekerProfile->user_id);
    }
}
