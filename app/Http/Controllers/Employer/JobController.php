<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Jobs\CreateJob;
use App\Actions\Jobs\UpdateJob;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkArrangement;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    /**
     * The employer's own job postings.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        /** @var EmployerProfile $profile */
        $profile = $user->employerProfile;

        return Inertia::render('employer/jobs/Index', [
            'jobs' => $profile->jobs()
                ->withCount('applications')
                ->latest()
                ->paginate(10)
                ->through(fn (Job $job) => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                    'location_city' => $job->location_city,
                    'location_country' => $job->location_country,
                    'applications_count' => $job->applications_count,
                    'created_at' => $job->created_at?->diffForHumans(),
                ]),
        ]);
    }

    /**
     * Show the job posting form.
     */
    public function create(): Response
    {
        return Inertia::render('employer/jobs/Create', [
            'countries' => Country::cases(),
            'currencies' => Currency::cases(),
            'statuses' => JobStatus::cases(),
            'employmentTypes' => EmploymentType::options(),
            'workArrangements' => WorkArrangement::options(),
            'experienceLevels' => ExperienceLevel::options(),
            'educationLevels' => EducationLevel::options(),
        ]);
    }

    /**
     * Post a new job.
     */
    public function store(StoreJobRequest $request, CreateJob $createJob): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        /** @var EmployerProfile $profile */
        $profile = $user->employerProfile;

        /** @var array{title: string, description: string, skills: array<int, string>, location_country: string, location_city: string, salary_min: int, salary_max: int, currency: string, employment_type: string, work_arrangement: string, experience_level: string, education_level: string, status: string} $data */
        $data = $request->validated();

        $createJob->handle($profile, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Job posted.')]);

        return to_route('employer.jobs.index');
    }

    /**
     * Show the job edit form.
     */
    public function edit(Job $job): Response
    {
        Gate::authorize('update', $job);

        return Inertia::render('employer/jobs/Edit', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'skills' => $job->skills,
                'location_country' => $job->location_country,
                'location_city' => $job->location_city,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'currency' => $job->currency,
                'employment_type' => $job->employment_type->value ?? '',
                'work_arrangement' => $job->work_arrangement->value ?? '',
                'experience_level' => $job->experience_level->value ?? '',
                'education_level' => $job->education_level->value ?? '',
                'status' => $job->status,
            ],
            'countries' => Country::cases(),
            'currencies' => Currency::cases(),
            'statuses' => JobStatus::cases(),
            'employmentTypes' => EmploymentType::options(),
            'workArrangements' => WorkArrangement::options(),
            'experienceLevels' => ExperienceLevel::options(),
            'educationLevels' => EducationLevel::options(),
        ]);
    }

    /**
     * Update a job posting.
     */
    public function update(UpdateJobRequest $request, Job $job, UpdateJob $updateJob): RedirectResponse
    {
        /** @var array{title: string, description: string, skills: array<int, string>, location_country: string, location_city: string, salary_min: int, salary_max: int, currency: string, employment_type: string, work_arrangement: string, experience_level: string, education_level: string, status: string} $data */
        $data = $request->validated();

        $updateJob->handle($job, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Job updated.')]);

        return to_route('employer.jobs.index');
    }
}
