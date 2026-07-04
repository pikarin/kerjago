<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicantsController extends Controller
{
    /**
     * Applicants for one of the employer's jobs.
     */
    public function index(Job $job): Response
    {
        Gate::authorize('viewApplicants', $job);

        return Inertia::render('employer/jobs/Applicants', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'status' => $job->status,
            ],
            'applications' => $job->applications()
                ->with('jobseekerProfile')
                ->latest()
                ->paginate(15)
                ->through(fn (Application $application) => [
                    'id' => $application->id,
                    'status' => $application->status,
                    'cover_note' => $application->cover_note,
                    'has_resume' => $application->resume_path !== null,
                    'applied_at' => $application->created_at?->diffForHumans(),
                    'profile' => [
                        'id' => $application->jobseekerProfile->id,
                        'full_name' => $application->jobseekerProfile->full_name,
                        'current_title' => $application->jobseekerProfile->current_title,
                        'skills' => $application->jobseekerProfile->skills,
                        'experience_years' => $application->jobseekerProfile->experience_years,
                        'country' => $application->jobseekerProfile->country,
                        'city' => $application->jobseekerProfile->city,
                        'phone' => $application->jobseekerProfile->phone,
                    ],
                ]),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }
}
