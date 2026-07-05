<?php

namespace App\Http\Resources;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full job posting shape for the public detail view, shared by the Inertia
 * show page and any future JSON API (ADR 0003).
 *
 * @mixin Job
 */
class JobDetailResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'skills' => $this->skills,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'employment_type' => $this->employment_type,
            'work_arrangement' => $this->work_arrangement,
            'experience_level' => $this->experience_level,
            'education_level' => $this->education_level,
            'posted_at' => $this->created_at?->diffForHumans(),
            'company' => [
                'name' => $this->employerProfile->company_name,
                'industry' => $this->employerProfile->industry,
                'city' => $this->employerProfile->city,
                'country' => $this->employerProfile->country,
                'website' => $this->employerProfile->website,
            ],
        ];
    }
}
