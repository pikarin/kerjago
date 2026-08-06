<?php

namespace App\Http\Resources;

use App\Models\JobseekerLanguage;
use App\Models\JobseekerProfile;
use App\Support\Masking\Mask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Candidate card shape for talent search results, shared by the Inertia
 * talent index and any future JSON API (ADR 0003).
 *
 * Candidates are locked by default: without an active Candidate Unlock the raw
 * name, email and phone numbers never leave the server — the client receives the
 * masked strings and could not reveal them if it tried. The avatar is the one
 * exception and is sent either way; the lock icon over it is decoration (ADR 0013).
 *
 * Everything a search filters on — skills, location, salary, age, education —
 * stays unmasked. Masking the search value would make search useless.
 *
 * @mixin JobseekerProfile
 */
class TalentSummaryResource extends JsonResource
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
