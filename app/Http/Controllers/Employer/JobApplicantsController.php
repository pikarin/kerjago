<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Applications\ApplyToJob;
use App\Actions\Unlocks\CountJobUnlocksUsed;
use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicantResource;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class JobApplicantsController extends Controller
{
    /**
     * Applicants for one of the employer's jobs.
     *
     * Which of them are unlocked is resolved once for the whole page rather
     * than per row: one indexed lookup, however long the list.
     */
    public function index(
        Job $job,
        ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
        CountJobUnlocksUsed $countJobUnlocksUsed,
    ): Response {
        Gate::authorize('viewApplicants', $job);

        $applications = $job->applications()
            ->with(['jobseekerProfile.user:id,email'])
            // ULIDs sort by creation, so the id is a stable tiebreak for
            // applications sharing a timestamp. Without it the order of tied
            // rows is undefined and a candidate can appear on two pages, or on
            // neither.
            ->latest()
            ->orderByDesc('id')
            ->paginate(15);

        $unlocked = $resolveUnlockedProfileIds->handle(
            $job->employerProfile,
            array_values($applications->getCollection()
                ->map(fn (Application $application): string => $application->jobseeker_profile_id)
                ->all()),
        );

        return Inertia::render('employer/jobs/Applicants', [
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'status' => $job->status,
                'expires_at' => $job->expires_at?->toIso8601String(),
            ],
            'unlockQuota' => [
                'used' => $countJobUnlocksUsed->handle($job),
                'total' => ApplyToJob::AUTO_UNLOCK_QUOTA,
            ],
            'applications' => $applications->through(fn (Application $application) => (new ApplicantResource(
                $application,
                isset($unlocked[$application->jobseeker_profile_id]),
            ))->resolve()),
            'statuses' => ApplicationStatus::cases(),
        ]);
    }
}
