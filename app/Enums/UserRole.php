<?php

namespace App\Enums;

enum UserRole: string
{
    case Employer = 'employer';
    case Jobseeker = 'jobseeker';
    case Staff = 'staff';

    /**
     * Roles a person may assign to themselves at registration.
     *
     * Staff is provisioned internally and must never be self-assignable —
     * see docs/adr/0010-staff-role.md.
     *
     * @return list<self>
     */
    public static function selfServiceCases(): array
    {
        return [self::Employer, self::Jobseeker];
    }
}
