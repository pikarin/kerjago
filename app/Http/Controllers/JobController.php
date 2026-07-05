<?php

namespace App\Http\Controllers;

use App\Actions\Jobs\SearchJobs;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkArrangement;
use App\Http\Requests\SearchJobsRequest;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    /**
     * Publicly searchable list of active jobs.
     */
    public function index(SearchJobsRequest $request, SearchJobs $searchJobs): Response
    {
        $filters = $request->filters();

        $result = $searchJobs->handle($filters);

        return Inertia::render('jobs/Index', [
            'jobs' => $result->jobs->through(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'company_name' => $job->employerProfile->company_name,
                'location_city' => $job->location_city,
                'location_country' => $job->location_country,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'currency' => $job->currency,
                'employment_type' => $job->employment_type,
                'work_arrangement' => $job->work_arrangement,
                'experience_level' => $job->experience_level,
                'skills' => $job->skills,
                'posted_at' => $job->created_at?->diffForHumans(),
            ]),
            // Cast so an empty filter set serializes as {} rather than [] —
            // a JSON array would make `filters.sort` resolve to
            // Array.prototype.sort on the client.
            'filters' => (object) $filters,
            'facets' => $result->facets,
            'facetsAvailable' => $result->facetsAvailable,
            'facetOptions' => [
                'country' => Country::options(),
                'employment_type' => EmploymentType::options(),
                'work_arrangement' => WorkArrangement::options(),
                'experience_level' => ExperienceLevel::options(),
                'education_level' => EducationLevel::options(),
            ],
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
