<?php

namespace App\Enums;

/**
 * What conferred a verification decision. Staff is the only one issued today;
 * System is the seam for anything the platform decides on its own, and exists
 * now so adding it later needs no migration. Mirrors UnlockSource.
 */
enum VerificationSource: string
{
    case Staff = 'staff';
    case System = 'system';
}
