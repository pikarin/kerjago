<?php

namespace App\Actions\Profiles;

use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertJobseekerProfile
{
    /**
     * Create or update the jobseeker profile, replacing the stored resume
     * when a new one is uploaded and syncing the work-experience rows to the
     * submitted list (rows absent from the payload are deleted).
     *
     * @param  array{full_name: string, current_title: string, preferred_job_title?: string|null, skills: array<int, string>, experience_years: int, country: string, city: string, preferred_country?: string|null, preferred_city?: string|null, availability?: string|null, languages?: array<int, string>|null, gender?: string|null, education_level?: string|null, phone: string|null}  $data
     * @param  list<array{id?: string|null, job_title: string, company_name: string, start_date: string, end_date?: string|null}>  $experiences
     */
    public function handle(User $user, array $data, ?UploadedFile $resume = null, array $experiences = []): JobseekerProfile
    {
        $profile = $user->jobseekerProfile ?? new JobseekerProfile;

        if ($resume !== null) {
            if ($profile->resume_path !== null) {
                Storage::disk('local')->delete($profile->resume_path);
            }

            $data['resume_path'] = $resume->store('resumes', 'local');
        }

        return DB::transaction(function () use ($profile, $user, $data, $experiences) {
            $profile->fill($data);
            $profile->user()->associate($user);
            $profile->save();

            $this->syncExperiences($profile, $experiences);

            return $profile;
        });
    }

    /**
     * Replace the profile's work experiences with the submitted rows,
     * preserving payload order via the sort column. Ids are matched within
     * the profile's own rows only, so a foreign id cannot be adopted.
     *
     * @param  list<array{id?: string|null, job_title: string, company_name: string, start_date: string, end_date?: string|null}>  $experiences
     */
    private function syncExperiences(JobseekerProfile $profile, array $experiences): void
    {
        $keepIds = array_values(array_filter(array_column($experiences, 'id')));

        $profile->workExperiences()->whereNotIn('id', $keepIds)->delete();

        $existing = $profile->workExperiences()->whereIn('id', $keepIds)->get()->keyBy('id');

        foreach ($experiences as $index => $row) {
            $attributes = [
                'job_title' => $row['job_title'],
                'company_name' => $row['company_name'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'] ?? null,
                'sort' => $index,
            ];

            $experience = $existing->get($row['id'] ?? null);

            if ($experience !== null) {
                $experience->update($attributes);
            } else {
                $profile->workExperiences()->create($attributes);
            }
        }
    }
}
