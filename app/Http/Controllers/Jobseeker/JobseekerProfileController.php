<?php

namespace App\Http\Controllers\Jobseeker;

use App\Actions\Profiles\UpsertJobseekerProfile;
use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\LanguageProficiency;
use App\Enums\SalaryPeriod;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertJobseekerProfileRequest;
use App\Models\Education;
use App\Models\JobseekerLanguage;
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
                'current_company' => $profile->current_company,
                'preferred_job_title' => $profile->preferred_job_title,
                'summary' => $profile->summary,
                'skills' => $profile->skills,
                'experience_years' => $profile->experience_years,
                'country' => $profile->country,
                'state' => $profile->state,
                'city' => $profile->city,
                'preferred_country' => $profile->preferred_country,
                'preferred_state' => $profile->preferred_state,
                'preferred_city' => $profile->preferred_city,
                'expected_salary_min' => $profile->expected_salary_min,
                'expected_salary_max' => $profile->expected_salary_max,
                'expected_salary_currency' => $profile->expected_salary_currency,
                'expected_salary_period' => $profile->expected_salary_period,
                'availability' => $profile->availability,
                'gender' => $profile->gender,
                'date_of_birth' => $profile->date_of_birth?->format('Y-m-d'),
                'phone' => $profile->phone,
                'whatsapp' => $profile->whatsapp,
                'has_avatar' => $profile->avatar_path !== null,
                'has_resume' => $profile->resume_path !== null,
                'experiences' => $profile->workExperiences->map(fn (WorkExperience $experience) => [
                    'id' => $experience->id,
                    'job_title' => $experience->job_title,
                    'company_name' => $experience->company_name,
                    'description' => $experience->description,
                    'start_date' => $experience->start_date->format('Y-m-d'),
                    'end_date' => $experience->end_date?->format('Y-m-d'),
                    'is_current' => $experience->is_current,
                ])->all(),
                'educations' => $profile->educations->map(fn (Education $education) => [
                    'id' => $education->id,
                    'institution' => $education->institution,
                    'field_of_study' => $education->field_of_study,
                    'level' => $education->level,
                    'start_date' => $education->start_date?->format('Y-m-d'),
                    'end_date' => $education->end_date?->format('Y-m-d'),
                ])->all(),
                'languages' => $profile->languages->map(fn (JobseekerLanguage $language) => [
                    'id' => $language->id,
                    'language' => $language->language,
                    'proficiency' => $language->proficiency,
                ])->all(),
            ],
            'countries' => Country::cases(),
            'availabilityOptions' => Availability::options(),
            'languageOptions' => Language::options(),
            'languageProficiencyOptions' => LanguageProficiency::options(),
            'genderOptions' => Gender::options(),
            'educationLevelOptions' => EducationLevel::options(),
            'currencyOptions' => Currency::cases(),
            'salaryPeriodOptions' => SalaryPeriod::options(),
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

        /** @var array<string, mixed> $data */
        $data = $request->safe()->except('resume', 'avatar', 'experiences', 'educations', 'languages');

        /** @var list<array{id?: string|null, job_title: string, company_name: string, description?: string|null, start_date: string, end_date?: string|null, is_current?: bool}> $experiences */
        $experiences = $request->validated('experiences') ?? [];

        /** @var list<array{id?: string|null, institution: string, field_of_study?: string|null, level?: string|null, start_date?: string|null, end_date?: string|null}> $educations */
        $educations = $request->validated('educations') ?? [];

        /** @var list<array{id?: string|null, language: string, proficiency: string}> $languages */
        $languages = $request->validated('languages') ?? [];

        $upsertJobseekerProfile->handle(
            $user,
            $data,
            $request->file('resume'),
            $experiences,
            $educations,
            $languages,
            $request->file('avatar'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Profile saved.')]);

        return to_route('jobseeker.profile.edit');
    }
}
