<?php

namespace App\Actions\Profiles;

use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpsertJobseekerProfile
{
    /**
     * Create or update the jobseeker profile, replacing the stored resume when a new one is uploaded.
     *
     * @param  array{full_name: string, current_title: string, skills: array<int, string>, experience_years: int, country: string, city: string, phone: string|null}  $data
     */
    public function handle(User $user, array $data, ?UploadedFile $resume = null): JobseekerProfile
    {
        $profile = $user->jobseekerProfile ?? new JobseekerProfile;

        if ($resume !== null) {
            if ($profile->resume_path !== null) {
                Storage::disk('local')->delete($profile->resume_path);
            }

            $data['resume_path'] = $resume->store('resumes', 'local');
        }

        $profile->fill($data);
        $profile->user()->associate($user);
        $profile->save();

        return $profile;
    }
}
