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

    /**
     * The statuses the form may set on a job that already exists.
     *
     * A job's *current* status is always allowed, or a live ad could not be
     * edited at all without first being taken offline — re-sending the status
     * it already has is a no-op, not a transition, so it cannot be used to
     * publish or to extend the expiry clock.
     *
     * @return list<JobStatus>
     */
    public static function editableCasesFor(JobStatus $current): array
    {
        $cases = self::editableCases();

        return in_array($current, $cases, true) ? $cases : [$current, ...$cases];
    }
}
