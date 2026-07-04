<?php

namespace App\Http\Controllers;

use App\Actions\Jobs\SearchJobs;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    /**
     * Publicly searchable list of active jobs.
     */
    public function index(Request $request, SearchJobs $searchJobs): Response
    {
        /** @var array{q?: string|null, country?: string|null, currency?: string|null, salary_min?: int|null, salary_max?: int|null} $filters */
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', Rule::enum(Country::class)],
            'currency' => ['nullable', Rule::enum(Currency::class)],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0'],
        ]);

        return Inertia::render('jobs/Index', [
            'jobs' => $searchJobs->handle($filters)->through(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->employerProfile->company_name,
                'location_city' => $job->location_city,
                'location_country' => $job->location_country,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'currency' => $job->currency,
                'skills' => $job->skills,
                'posted_at' => $job->created_at?->diffForHumans(),
            ]),
            'filters' => $filters,
            'countries' => Country::cases(),
            'currencies' => Currency::cases(),
        ]);
    }

    /**
     * Public job detail page.
     */
    public function show(Request $request, Job $job): Response
    {
        abort_unless($job->status === JobStatus::Active, 404);

        $job->load('employerProfile');

        /** @var User|null $user */
        $user = $request->user();
        $jobseekerProfile = $user?->jobseekerProfile;

        return Inertia::render('jobs/Show', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'skills' => $job->skills,
                'location_city' => $job->location_city,
                'location_country' => $job->location_country,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'currency' => $job->currency,
                'posted_at' => $job->created_at?->diffForHumans(),
                'company' => [
                    'name' => $job->employerProfile->company_name,
                    'industry' => $job->employerProfile->industry,
                    'city' => $job->employerProfile->city,
                    'country' => $job->employerProfile->country,
                    'website' => $job->employerProfile->website,
                ],
            ],
            'viewer' => [
                'is_jobseeker' => $user?->isJobseeker() ?? false,
                'has_profile' => $jobseekerProfile !== null,
                'has_applied' => $jobseekerProfile !== null
                    && $job->applications()->whereBelongsTo($jobseekerProfile)->exists(),
            ],
        ]);
    }
}
