<?php

namespace App\Actions\Jobs;

use App\Enums\JobStatus;
use App\Models\Job;

/**
 * Put a job ad live for its 45-day window.
 *
 * Publishing is its own Action rather than a status value the edit form can set,
 * because it is the only moment the clock may be stamped: editing a live ad must
 * never extend it, and a re-published ad starts a fresh window without handing
 * back the unlock slots the previous run spent.
 */
class PublishJob
{
    public function handle(Job $job): Job
    {
        $publishedAt = now();

        $job->forceFill([
            'status' => JobStatus::Active,
            'published_at' => $publishedAt,
            'expires_at' => $publishedAt->addDays(Job::PUBLISH_WINDOW_DAYS),
        ])->save();

        return $job;
    }
}
