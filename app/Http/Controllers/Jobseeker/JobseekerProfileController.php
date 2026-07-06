<?php

namespace App\Http\Controllers\Jobseeker;

use App\Actions\Profiles\UpsertJobseekerProfile;
use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertJobseekerProfileRequest;
use App\Models\User;
use App\Models\WorkExperience;
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
                'preferred_job_title' => $profile->preferred_job_title,
                'skills' => $profile->skills,
                'experience_years' => $profile->experience_years,
                'country' => $profile->country,
                'city' => $profile->city,
                'preferred_country' => $profile->preferred_country,
                'preferred_city' => $profile->preferred_city,
                'availability' => $profile->availability,
                'languages' => $profile->languages ?? [],
                'gender' => $profile->gender,
                'education_level' => $profile->education_level,
                'phone' => $profile->phone,
                'has_resume' => $profile->resume_path !== null,
                'experiences' => $profile->workExperiences->map(fn (WorkExperience $experience) => [
                    'id' => $experience->id,
                    'job_title' => $experience->job_title,
                    'company_name' => $experience->company_name,
                    'start_date' => $experience->start_date->format('Y-m-d'),
                    'end_date' => $experience->end_date?->format('Y-m-d'),
                ])->all(),
            ],
            'countries' => Country::cases(),
            'availabilityOptions' => Availability::options(),
            'languageOptions' => Language::options(),
            'genderOptions' => Gender::options(),
            'educationLevelOptions' => EducationLevel::options(),
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

        /** @var array{full_name: string, current_title: string, preferred_job_title?: string|null, skills: array<int, string>, experience_years: int, country: string, city: string, preferred_country?: string|null, preferred_city?: string|null, availability?: string|null, languages?: array<int, string>|null, gender?: string|null, education_level?: string|null, phone: string|null} $data */
        $data = $request->safe()->except('resume', 'experiences');

        /** @var list<array{id?: string|null, job_title: string, company_name: string, start_date: string, end_date?: string|null}> $experiences */
        $experiences = $request->validated('experiences') ?? [];

        $upsertJobseekerProfile->handle($user, $data, $request->file('resume'), $experiences);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile saved.')]);

        return to_route('jobseeker.profile.edit');
    }
}
