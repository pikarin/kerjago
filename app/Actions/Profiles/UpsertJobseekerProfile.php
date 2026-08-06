<?php

namespace App\Actions\Profiles;

use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UpsertJobseekerProfile
{
    /**
     * Create or update the jobseeker profile, replacing stored uploads when
     * new ones arrive and syncing each child collection to the submitted
     * list (rows absent from the payload are deleted).
     *
     * @param  array<string, mixed>  $data
     * @param  list<array{id?: string|null, job_title: string, company_name: string, description?: string|null, start_date: string, end_date?: string|null, is_current?: bool}>  $experiences
     * @param  list<array{id?: string|null, institution: string, field_of_study?: string|null, level?: string|null, start_date?: string|null, end_date?: string|null}>  $educations
     * @param  list<array{id?: string|null, language: string, proficiency: string}>  $languages
     */
    public function handle(
        User $user,
        array $data,
        ?UploadedFile $resume = null,
        array $experiences = [],
        array $educations = [],
        array $languages = [],
        ?UploadedFile $avatar = null,
    ): JobseekerProfile {
        $profile = $user->jobseekerProfile ?? new JobseekerProfile;

        if ($resume !== null) {
            $data['resume_path'] = $this->replaceUpload($profile->resume_path, $resume, 'resumes');
        }

        if ($avatar !== null) {
            $data['avatar_path'] = $this->replaceUpload($profile->avatar_path, $avatar, 'avatars');
        }

        return DB::transaction(function () use ($profile, $user, $data, $experiences, $educations, $languages) {
            $profile->fill($data);
            $profile->user()->associate($user);
            $profile->save();

            $this->syncRows(fn () => $profile->workExperiences(), $experiences, [
                'job_title' => null,
                'company_name' => null,
                'description' => null,
                'start_date' => null,
                'end_date' => null,
                'is_current' => false,
            ]);

            $this->syncRows(fn () => $profile->educations(), $educations, [
                'institution' => null,
                'field_of_study' => null,
                'level' => null,
                'start_date' => null,
                'end_date' => null,
            ]);

            $this->syncRows(fn () => $profile->languages(), $languages, [
                'language' => null,
                'proficiency' => null,
            ]);

            return $profile;
        });
    }

    /**
     * Store a new upload on the private disk and delete the one it replaces.
     *
     * @throws RuntimeException when the disk rejects the write, so a failed
     *                          upload rolls the surrounding transaction back
     *                          instead of silently writing a `false` path.
     */
    private function replaceUpload(?string $currentPath, UploadedFile $file, string $directory): string
    {
        if ($currentPath !== null) {
            Storage::disk('local')->delete($currentPath);
        }

        $path = $file->store($directory, 'local');

        if ($path === false) {
            throw new RuntimeException("Failed to store the uploaded file in {$directory}.");
        }

        return $path;
    }

    /**
     * Replace a child collection with the submitted rows, preserving payload
     * order via the sort column. Ids are matched within the profile's own
     * rows only, so a foreign id cannot be adopted.
     *
     * The relation is resolved through a factory rather than passed in, because
     * constraining a HasMany mutates its shared query builder — a single
     * instance used twice would AND the delete's whereNotIn onto the
     * subsequent whereIn and match nothing.
     *
     * @param  callable(): HasMany<covariant Model, JobseekerProfile>  $relation
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, mixed>  $columns  Column => value used when the payload omits it.
     */
    private function syncRows(callable $relation, array $rows, array $columns): void
    {
        $keepIds = array_values(array_filter(array_column($rows, 'id')));

        $relation()->whereNotIn('id', $keepIds)->delete();

        $existing = $relation()->whereIn('id', $keepIds)->get()->keyBy('id');

        foreach ($rows as $index => $row) {
            $attributes = ['sort' => $index];

            foreach ($columns as $column => $default) {
                $attributes[$column] = $row[$column] ?? $default;
            }

            $id = $row['id'] ?? null;
            $model = is_string($id) ? $existing->get($id) : null;

            if ($model instanceof Model) {
                $model->update($attributes);
            } else {
                $relation()->create($attributes);
            }
        }
    }
}
