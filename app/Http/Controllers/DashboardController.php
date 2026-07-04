<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Role-specific dashboard.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        return $user->isEmployer()
            ? $this->employerDashboard($user)
            : $this->jobseekerDashboard($user);
    }

    private function employerDashboard(User $user): Response
    {
        $profile = $user->employerProfile;

        return Inertia::render('dashboard/Employer', [
            'hasProfile' => $profile !== null,
            'stats' => $profile === null ? null : [
                'active_jobs' => $profile->jobs()->active()->count(),
                'total_jobs' => $profile->jobs()->count(),
                'total_applicants' => Application::query()
                    ->whereIn('job_id', $profile->jobs()->select('id'))
                    ->count(),
            ],
            'recentJobs' => $profile?->jobs()
                ->withCount('applications')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Job $job) => [
                    'id' => $job->id,
                    'title' => $job->title,
                    'status' => $job->status,
                    'applications_count' => $job->applications_count,
                ]) ?? [],
        ]);
    }

    private function jobseekerDashboard(User $user): Response
    {
        $profile = $user->jobseekerProfile;

        return Inertia::render('dashboard/Jobseeker', [
            'hasProfile' => $profile !== null,
            'stats' => $profile === null ? null : [
                'total_applications' => $profile->applications()->count(),
                'shortlisted' => $profile->applications()
                    ->where('status', ApplicationStatus::Shortlisted)
                    ->count(),
            ],
            'recentApplications' => $profile?->applications()
                ->with('job.employerProfile')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (Application $application) => [
                    'id' => $application->id,
                    'status' => $application->status,
                    'job_title' => $application->job->title,
                    'job_id' => $application->job->id,
                    'company_name' => $application->job->employerProfile->company_name,
                ]) ?? [],
        ]);
    }
}
