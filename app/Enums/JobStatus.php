<?php

namespace App\Enums;

enum JobStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';
    case Expired = 'expired';

    /**
     * The statuses an employer may set from the job form.
     *
     * Active is absent on purpose: going live stamps the 45-day clock, so it
     * runs through PublishJob rather than through a dropdown value that an
     * ordinary edit could re-send. Expired is set by the daily sweep only.
     *
     * @return list<JobStatus>
     */
    public static function editableCases(): array
    {
        return [self::Draft, self::Closed];
    }
}
