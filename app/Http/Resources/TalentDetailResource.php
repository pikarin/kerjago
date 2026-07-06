<?php

namespace App\Http\Resources;

use App\Models\JobseekerProfile;
use App\Models\WorkExperience;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full candidate profile for the employer talent detail page. Contact
 * details (phone, email) stay excluded until quota/consent gating ships;
 * resumes are only shared through applications (ADR 0006).
 *
 * @mixin JobseekerProfile
 */
class TalentDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'current_title' => $this->current_title,
            'preferred_job_title' => $this->preferred_job_title,
            'skills' => $this->skills,
            'experience_years' => $this->experience_years,
            'country' => $this->country,
            'city' => $this->city,
            'preferred_country' => $this->preferred_country,
            'preferred_city' => $this->preferred_city,
            'availability' => $this->availability,
            'languages' => $this->languages,
            'gender' => $this->gender,
            'education_level' => $this->education_level,
            'work_experiences' => $this->workExperiences->map(fn (WorkExperience $experience) => [
                'id' => $experience->id,
                'job_title' => $experience->job_title,
                'company_name' => $experience->company_name,
                'start_date' => $experience->start_date->format('Y-m'),
                'end_date' => $experience->end_date?->format('Y-m'),
                'is_current' => $experience->end_date === null,
            ])->all(),
        ];
    }
}
