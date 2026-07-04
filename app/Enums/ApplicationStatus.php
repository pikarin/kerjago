<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case Reviewed = 'reviewed';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
}
