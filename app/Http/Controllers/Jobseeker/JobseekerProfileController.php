<?php

namespace App\Http\Controllers\Jobseeker;

use App\Actions\Profiles\UpsertJobseekerProfile;
use App\Enums\Country;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertJobseekerProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobseekerProfileController extends Controller
{
    /**
     * Show the jobseeker profile form.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->jobseekerProfile;

        return Inertia::render('jobseeker/profile/Edit', [
            'profile' => $profile === null ? null : [
                'full_name' => $profile->full_name,
                'current_title' => $profile->current_title,
                'skills' => $profile->skills,
                'experience_years' => $profile->experience_years,
                'country' => $profile->country,
                'city' => $profile->city,
                'phone' => $profile->phone,
                'has_resume' => $profile->resume_path !== null,
            ],
            'countries' => Country::cases(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Create or update the jobseeker profile.
     */
    public function update(
        UpsertJobseekerProfileRequest $request,
        UpsertJobseekerProfile $upsertJobseekerProfile,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var array{full_name: string, current_title: string, skills: array<int, string>, experience_years: int, country: string, city: string, phone: string|null} $data */
        $data = $request->safe()->except('resume');

        $upsertJobseekerProfile->handle($user, $data, $request->file('resume'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile saved.')]);

        return to_route('jobseeker.profile.edit');
    }
}
