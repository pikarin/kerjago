<?php

namespace App\Http\Resources;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Job card shape for search results, shared by the Inertia jobs index and
 * any future JSON API (ADR 0003).
 *
 * @mixin Job
 */
class JobSummaryResource extends JsonResource
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
            'company_name' => $this->employerProfile->company_name,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'employment_type' => $this->employment_type,
            'work_arrangement' => $this->work_arrangement,
            'experience_level' => $this->experience_level,
            'skills' => $this->skills,
            'posted_at' => $this->created_at?->diffForHumans(),
        ];
    }
}
