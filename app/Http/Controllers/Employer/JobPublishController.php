<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Jobs\PublishJob;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class JobPublishController extends Controller
{
    /**
     * Put an ad live for 45 days, or start a fresh window on an expired one.
     *
     * Its own endpoint because publishing is the only moment the expiry clock
     * may be stamped — an ordinary edit must never extend a running ad.
     *
     * Idempotent rather than guarded: PublishJob refuses to restamp a running
     * window itself, so a double-clicked Publish button is a no-op worth
     * reporting rather than an error. Returning 409 here would also collide
     * with the status Inertia reserves for its own redirects, surfacing a raw
     * error page in a modal.
     */
    public function store(Job $job, PublishJob $publishJob): RedirectResponse
    {
        Gate::authorize('update', $job);

        $wasLive = $job->isPublished();

        $publishJob->handle($job);

        // A gate declined it. Not an error — the ad is written, submitted and
        // waiting, and the employer's own banner explains what it is waiting
        // on.
        if ($job->status === JobStatus::Pending) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('This job is waiting to be published. It goes live as soon as your company is verified.'),
            ]);

            return to_route('employer.jobs.index');
        }

        if ($wasLive) {
            Inertia::flash('toast', [
                'type' => 'info',
                'message' => __('This job is already live. It expires on :date.', [
                    'date' => $job->expires_at?->toFormattedDateString(),
                ]),
            ]);

            return to_route('employer.jobs.index');
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Job published. It expires on :date.', [
                'date' => $job->expires_at?->toFormattedDateString(),
            ]),
        ]);

        return to_route('employer.jobs.index');
    }
}
