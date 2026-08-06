<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Jobs\PublishJob;
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
     */
    public function store(Job $job, PublishJob $publishJob): RedirectResponse
    {
        Gate::authorize('update', $job);

        $publishJob->handle($job);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Job published. It expires on :date.', [
                'date' => $job->expires_at?->toFormattedDateString(),
            ]),
        ]);

        return to_route('employer.jobs.index');
    }
}
