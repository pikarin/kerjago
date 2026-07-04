<?php

namespace App\Http\Controllers;

use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Public landing page with live marketplace stats and the freshest openings.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Welcome', [
            'stats' => [
                'active_jobs' => Job::query()->active()->count(),
                'companies' => EmployerProfile::query()->count(),
                'candidates' => JobseekerProfile::query()->count(),
            ],
            'latestJobs' => Job::query()
                ->active()
                ->with('employerProfile')
                ->latest()
                ->limit(6)
                ->get()
                ->map(fn (Job $job) => [
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
        ]);
    }
}
