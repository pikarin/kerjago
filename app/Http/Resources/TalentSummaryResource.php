<?php

namespace App\Http\Resources;

use App\Models\JobseekerLanguage;
use App\Models\JobseekerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Candidate card shape for talent search results, shared by the Inertia
 * talent index and any future JSON API (ADR 0003). Contact details (phone,
 * WhatsApp, email), the raw birth date, and resumes are deliberately
 * excluded — contact reveal ships later behind employer quota and jobseeker
 * consent. Age is exposed; the birth date it derives from is not.
 *
 * @mixin JobseekerProfile
 */
class TalentSummaryResource extends JsonResource
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
            'availability' => $this->availability,
            'gender' => $this->gender,
            'age' => $this->age(),
            'languages' => $this->languages->map(fn (JobseekerLanguage $language) => [
                'language' => $language->language,
                'proficiency' => $language->proficiency,
            ])->all(),
            'education_level' => $this->highestEducationLevel(),
            'expected_salary_min' => $this->expected_salary_min,
            'expected_salary_max' => $this->expected_salary_max,
            'expected_salary_currency' => $this->expected_salary_currency,
            'expected_salary_period' => $this->expected_salary_period,
            'profile_views_count' => $this->profile_views_count,
            'resume_downloads_count' => $this->resume_downloads_count,
            'employer_actions_count' => $this->employer_actions_count,
            'last_active_at' => $this->last_active_at?->toIso8601String(),
        ];
    }
}
