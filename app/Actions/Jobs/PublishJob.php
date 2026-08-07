<?php

namespace App\Actions\Jobs;

use App\Enums\EmployerCapability;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Support\Capabilities\EmployerCapabilities;

/**
 * Put a job ad live, or park it where a capability gate left it.
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
 *
 * The capability check lives here rather than in middleware or a policy because
 * this Action is reached from outside HTTP — the batch that runs when a company
 * is verified calls it directly, and that is precisely the path where letting
 * an ad through would put it live for a company that may not have one. One
 * Action, both invariants.
 */
class PublishJob
{
    public function __construct(private EmployerCapabilities $capabilities) {}

    public function handle(Job $job): Job
    {
        // Nothing to do, and worth returning early: an unconditional save still
        // fires the `saved` event, so a double-clicked Publish button would
        // cost a Typesense upsert per click.
        if ($job->isPublished()) {
            return $job;
        }

        if ($this->capabilities->for($job->employerProfile, EmployerCapability::PublishJob)->isDenied()) {
            return $this->park($job);
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

    /**
     * The ad was written and submitted; a gate declined it. It waits in Pending
     * with **no clock stamped**, so the 45 days start when it actually goes
     * live rather than burning while it is invisible.
     */
    private function park(Job $job): Job
    {
        // Same reasoning as the isPublished() guard: re-submitting an ad that is
        // already parked should not cost a write and a Scout round trip.
        if ($job->status === JobStatus::Pending) {
            return $job;
        }

        $job->forceFill(['status' => JobStatus::Pending])->save();

        return $job;
    }
}
