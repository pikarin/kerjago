<?php

namespace App\Http\Resources;

use App\Models\Application;
use App\Support\Masking\Mask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of a job's applicant list.
 *
 * The first ten applicants to a job are unlocked automatically; everyone after
 * them is masked exactly as they would be in Talent Search, because the lock is
 * a fact about the candidate's PII rather than about the surface it is rendered
 * on. `has_resume` reports whether a snapshot exists, but the download itself is
 * refused without an unlock (ADR 0013).
 *
 * @mixin Application
 */
class ApplicantResource extends JsonResource
{
    /**
     * @param  bool  $isUnlocked  whether the viewing employer holds an active
     *                            Candidate Unlock for the applicant
     */
    public function __construct(Application $resource, private bool $isUnlocked = false)
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
        $profile = $this->jobseekerProfile;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'cover_note' => $this->cover_note,
            'has_resume' => $this->resume_path !== null,
            'can_download_resume' => $this->isUnlocked && $this->resume_path !== null,
            'applied_at' => $this->created_at?->diffForHumans(),
            'profile' => [
                'id' => $profile->id,
                'is_locked' => ! $this->isUnlocked,
                'full_name' => $this->isUnlocked ? $profile->full_name : Mask::name($profile->full_name),
                'email' => $this->isUnlocked ? $profile->user->email : Mask::email($profile->user->email),
                'phone' => $this->isUnlocked ? $profile->phone : Mask::phone($profile->phone),
                'whatsapp' => $this->isUnlocked ? $profile->whatsapp : Mask::phone($profile->whatsapp),
                'current_title' => $profile->current_title,
                'skills' => $profile->skills,
                'experience_years' => $profile->experience_years,
                'country' => $profile->country,
                'city' => $profile->city,
            ],
        ];
    }
}
