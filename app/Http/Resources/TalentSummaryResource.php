<?php

namespace App\Http\Resources;

use App\Models\JobseekerProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Candidate card shape for talent search results, shared by the Inertia
 * talent index and any future JSON API (ADR 0003). Contact details (phone,
 * email) and resumes are deliberately excluded — contact reveal ships later
 * behind employer quota and jobseeker consent.
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
            'preferred_job_title' => $this->preferred_job_title,
            'skills' => $this->skills,
            'experience_years' => $this->experience_years,
            'country' => $this->country,
            'city' => $this->city,
            'preferred_country' => $this->preferred_country,
            'preferred_city' => $this->preferred_city,
            'availability' => $this->availability,
        ];
    }
}
