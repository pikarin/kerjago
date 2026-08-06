<?php

namespace App\Actions\Unlocks;

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Support\Masking\Mask;
use Illuminate\Database\Query\Builder;

/**
 * The locked rows an employer's inbox shows alongside their real conversations.
 *
 * Built from applications, never from chat: a locked candidate's thread is
 * withheld precisely so its contents cannot reach the employer, and reading it
 * to render a teaser would hand back what the lock took away. So the teaser
 * carries no unread count, no last-activity time and no preview — each of those
 * is a signal about a person the employer holds no unlock for.
 *
 * @phpstan-type LockedTeaser array{application_id: string, job_id: string, job_title: string, display_name: string|null, current_title: string}
 */
class ListLockedApplicantTeasers
{
    /**
     * @return list<LockedTeaser>
     */
    public function handle(EmployerProfile $employerProfile, int $limit = 20): array
    {
        return array_values(Application::query()
            ->with(['jobseekerProfile:id,full_name,current_title', 'job:id,title'])
            ->whereHas('job', fn ($query) => $query->where('employer_profile_id', $employerProfile->id))
            ->whereNotExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('candidate_unlocks')
                ->whereColumn('candidate_unlocks.jobseeker_profile_id', 'applications.jobseeker_profile_id')
                ->where('candidate_unlocks.employer_profile_id', $employerProfile->id)
                ->where('candidate_unlocks.expires_at', '>', now()))
            // Tiebreak on the ULID, or which applicants survive the limit is
            // undefined among rows sharing a timestamp.
            ->latest()
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Application $application): array => [
                'application_id' => $application->id,
                'job_id' => $application->job->id,
                'job_title' => $application->job->title,
                'display_name' => Mask::name($application->jobseekerProfile->full_name),
                'current_title' => $application->jobseekerProfile->current_title,
            ])
            ->all());
    }
}
