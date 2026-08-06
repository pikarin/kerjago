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

        // Ownership is not the whole rule: re-posting this endpoint against an
        // ad that is already live would restamp published_at and push the
        // expiry another 45 days out, which is exactly what routing publishing
        // away from the edit form was meant to prevent.
        abort_if($job->isPublished(), 409);

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
