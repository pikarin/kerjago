<?php

namespace App\Http\Resources;

use App\Models\Education;
use App\Models\JobseekerLanguage;
use App\Models\JobseekerProfile;
use App\Models\WorkExperience;
use App\Support\Masking\Mask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full candidate profile for the employer talent detail page.
 *
 * Locked candidates have their name, email and phone numbers masked before the
 * response is built, so no raw value reaches the client (ADR 0013). Resumes are
 * only ever shared through applications (ADR 0006) and only to an employer
 * holding an active unlock — the CV carries every field the mask hides.
 *
 * @mixin JobseekerProfile
 */
class TalentDetailResource extends JsonResource
{
    /**
     * @param  bool  $isUnlocked  whether the viewing employer holds an active
     *                            Candidate Unlock for this profile
     */
    public function __construct(JobseekerProfile $resource, private bool $isUnlocked = false)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'is_locked' => ! $this->isUnlocked,
            'full_name' => $this->isUnlocked ? $this->full_name : Mask::name($this->full_name),
            'email' => $this->isUnlocked ? $this->user->email : Mask::email($this->user->email),
            'phone' => $this->isUnlocked ? $this->phone : Mask::phone($this->phone),
            'whatsapp' => $this->isUnlocked ? $this->whatsapp : Mask::phone($this->whatsapp),
            'current_title' => $this->current_title,
            'current_company' => $this->current_company,
            'preferred_job_title' => $this->preferred_job_title,
            'summary' => $this->summary,
            'skills' => $this->skills,
            'experience_years' => $this->experience_years,
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'preferred_country' => $this->preferred_country,
            'preferred_state' => $this->preferred_state,
            'preferred_city' => $this->preferred_city,
            'expected_salary_min' => $this->expected_salary_min,
            'expected_salary_max' => $this->expected_salary_max,
            'expected_salary_currency' => $this->expected_salary_currency,
            'expected_salary_period' => $this->expected_salary_period,
            'availability' => $this->availability,
            'gender' => $this->gender,
            'age' => $this->age(),
            'education_level' => $this->highestEducationLevel(),
            'profile_views_count' => $this->profile_views_count,
            'resume_downloads_count' => $this->resume_downloads_count,
            'employer_actions_count' => $this->employer_actions_count,
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'work_experiences' => $this->workExperiences->map(fn (WorkExperience $experience) => [
                'id' => $experience->id,
                'job_title' => $experience->job_title,
                'company_name' => $experience->company_name,
                'description' => $experience->description,
                'start_date' => $experience->start_date->format('Y-m'),
                'end_date' => $experience->end_date?->format('Y-m'),
                'is_current' => $experience->is_current,
            ])->all(),
            'educations' => $this->educations->map(fn (Education $education) => [
                'id' => $education->id,
                'institution' => $education->institution,
                'field_of_study' => $education->field_of_study,
                'level' => $education->level,
                'start_date' => $education->start_date?->format('Y-m'),
                'end_date' => $education->end_date?->format('Y-m'),
            ])->all(),
            'languages' => $this->languages->map(fn (JobseekerLanguage $language) => [
                'id' => $language->id,
                'language' => $language->language,
                'proficiency' => $language->proficiency,
            ])->all(),
        ];
    }
}
