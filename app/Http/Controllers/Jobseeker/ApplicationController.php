<?php

namespace App\Http\Controllers\Jobseeker;

use App\Actions\Applications\ApplyToJob;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\Application;
use App\Models\Job;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    /**
     * The jobseeker's applications with their current statuses.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        /** @var JobseekerProfile $profile */
        $profile = $user->jobseekerProfile;

        return Inertia::render('jobseeker/applications/Index', [
            'applications' => $profile->applications()
                ->with('job.employerProfile')
                ->latest()
                ->paginate(10)
                ->through(fn (Application $application) => [
                    'id' => $application->id,
                    'status' => $application->status,
                    'applied_at' => $application->created_at?->diffForHumans(),
                    'job' => [
                        'id' => $application->job->id,
                        'title' => $application->job->title,
                        'status' => $application->job->status,
                        'company_name' => $application->job->employerProfile->company_name,
                        'location_city' => $application->job->location_city,
                        'location_country' => $application->job->location_country,
                    ],
                ]),
        ]);
    }

    /**
     * Apply to a job.
     */
    public function store(StoreApplicationRequest $request, Job $job, ApplyToJob $applyToJob): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var JobseekerProfile $profile */
        $profile = $user->jobseekerProfile;

        $applyToJob->handle($profile, $job, $request->coverNote());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Application submitted.')]);

        return to_route('jobseeker.applications.index');
    }
}
