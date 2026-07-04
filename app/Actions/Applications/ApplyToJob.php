<?php

namespace App\Actions\Applications;

use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\JobseekerProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplyToJob
{
    /**
     * Submit an application, snapshotting the profile's resume so the
     * employer always sees the document that was actually submitted (ADR 0006).
     */
    public function handle(JobseekerProfile $profile, Job $job, ?string $coverNote = null): Application
    {
        if ($job->status !== JobStatus::Active) {
            throw ValidationException::withMessages([
                'job' => __('This job is no longer accepting applications.'),
            ]);
        }

        if ($job->applications()->whereBelongsTo($profile)->exists()) {
            throw ValidationException::withMessages([
                'job' => __('You have already applied to this job.'),
            ]);
        }

        $application = new Application([
            'resume_path' => $this->snapshotResume($profile),
            'cover_note' => $coverNote,
        ]);

        $application->job()->associate($job);
        $application->jobseekerProfile()->associate($profile);
        $application->save();

        return $application;
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
