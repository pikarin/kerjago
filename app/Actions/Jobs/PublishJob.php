<?php

namespace App\Actions\Jobs;

use App\Enums\JobStatus;
use App\Models\Job;

/**
 * Put a job ad live.
 *
 * Publishing is its own Action rather than a status value the edit form can
 * set, because it is the only moment the expiry clock may be stamped.
 *
 * The clock is stamped only when no window is already running. An ad whose term
 * still has time left — including one an employer has just moved back to Draft
 * or Closed — resumes on its remaining days instead of starting a fresh 45.
 * Keying that on the *timestamp* rather than on the status is what closes the
 * round trip: status is editable, `expires_at` is not, so "edit a live ad and
 * it keeps its window" holds however the employer gets there.
 *
 * A genuinely expired ad starts a new window, without recovering the unlock
 * slots the previous run spent.
 */
class PublishJob
{
    public function handle(Job $job): Job
    {
        // Nothing to do, and worth returning early: an unconditional save still
        // fires the `saved` event, so a double-clicked Publish button would
        // cost a Typesense upsert per click.
        if ($job->isPublished()) {
            return $job;
        }

        $attributes = ['status' => JobStatus::Active];

        if (! $job->hasRunningWindow()) {
            $publishedAt = now();

            $attributes['published_at'] = $publishedAt;
            $attributes['expires_at'] = $publishedAt->addDays(Job::PUBLISH_WINDOW_DAYS);
        }

        $job->forceFill($attributes)->save();

        return $job;
    }
}
