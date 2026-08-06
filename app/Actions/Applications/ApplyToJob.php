<?php

namespace App\Actions\Applications;

use App\Actions\Unlocks\IssueCandidateUnlock;
use App\Enums\UnlockSource;
use App\Models\Application;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplyToJob
{
    /**
     * How many applicants a published job unlocks for its employer at no cost.
     * Spent by application ordinal, and never refilled by re-publishing.
     */
    public const int AUTO_UNLOCK_QUOTA = 10;

    /**
     * A candidate unlocked by this quota stays visible for a year past the ad's
     * expiry — long enough to outlast a hiring cycle that started from it.
     */
    public const int AUTO_UNLOCK_YEARS_AFTER_EXPIRY = 1;

    public function __construct(private IssueCandidateUnlock $issueCandidateUnlock) {}

    /**
     * Submit an application, snapshotting the profile's resume so the
     * employer always sees the document that was actually submitted (ADR 0006).
     *
     * The first ten applicants to a job are unlocked for its employer. The count
     * is taken inside the transaction with the job row locked, so ten simultaneous
     * submissions cannot each read "nine used" and hand out an eleventh unlock.
     */
    public function handle(JobseekerProfile $profile, Job $job, ?string $coverNote = null): Application
    {
        if (! $job->isPublished()) {
            throw ValidationException::withMessages([
                'job' => __('This job is no longer accepting applications.'),
            ]);
        }

        if ($job->applications()->whereBelongsTo($profile)->exists()) {
            throw ValidationException::withMessages([
                'job' => __('You have already applied to this job.'),
            ]);
        }

        // Snapshotting copies a file, which a transaction cannot roll back —
        // so it happens before the transaction opens rather than inside it.
        $resumePath = $this->snapshotResume($profile);

        return DB::transaction(function () use ($profile, $job, $coverNote, $resumePath): Application {
            $lockedJob = Job::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();

            $application = new Application([
                'resume_path' => $resumePath,
                'cover_note' => $coverNote,
            ]);

            $application->job()->associate($lockedJob);
            $application->jobseekerProfile()->associate($profile);
            $application->save();

            $this->issueAutoUnlock($lockedJob, $profile);

            return $application;
        });
    }

    /**
     * Spend one of the job's ten slots on this applicant, if any are left.
     *
     * The slot is the application's ordinal, so a candidate the employer already
     * unlocked through another job still consumes one — "the first ten applicants"
     * stays literally true and recomputable from the applications themselves.
     */
    private function issueAutoUnlock(Job $job, JobseekerProfile $profile): void
    {
        // Ordinal of the application just inserted, not a count of unlocks
        // issued: an already-unlocked candidate collapses into their existing
        // row rather than creating one, and counting rows would silently hand
        // that slot to an eleventh applicant.
        $ordinal = $job->applications()->count();

        if ($ordinal > self::AUTO_UNLOCK_QUOTA) {
            return;
        }

        $expiresAt = $job->expires_at?->addYears(self::AUTO_UNLOCK_YEARS_AFTER_EXPIRY);

        if ($expiresAt === null) {
            return;
        }

        $this->issueCandidateUnlock->handle(
            employerProfile: $job->employerProfile,
            jobseekerProfile: $profile,
            expiresAt: $expiresAt,
            source: UnlockSource::AutoFirstTen,
            job: $job,
        );
    }

    private function snapshotResume(JobseekerProfile $profile): ?string
    {
        if ($profile->resume_path === null || ! Storage::disk('local')->exists($profile->resume_path)) {
            return null;
        }

        $extension = pathinfo($profile->resume_path, PATHINFO_EXTENSION);
        $snapshotPath = 'applications/'.Str::ulid().($extension !== '' ? ".{$extension}" : '');

        Storage::disk('local')->copy($profile->resume_path, $snapshotPath);

        return $snapshotPath;
    }
}
