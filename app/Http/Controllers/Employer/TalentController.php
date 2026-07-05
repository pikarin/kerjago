<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Talent\SearchTalent;
use App\Enums\Country;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTalentRequest;
use App\Models\JobseekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TalentController extends Controller
{
    /**
     * Employer-facing talent search over jobseeker profiles.
     */
    public function index(SearchTalentRequest $request, SearchTalent $searchTalent): Response
    {
        $filters = $request->filters();

        return Inertia::render('employer/talent/Index', [
            'profiles' => $searchTalent->handle($filters)->through(fn (JobseekerProfile $profile) => [
                'id' => $profile->id,
                'full_name' => $profile->full_name,
                'current_title' => $profile->current_title,
                'skills' => $profile->skills,
                'experience_years' => $profile->experience_years,
                'country' => $profile->country,
                'city' => $profile->city,
            ]),
            'filters' => $filters,
            'countries' => Country::cases(),
        ]);
    }

    /**
     * A single candidate profile. Resume files are intentionally excluded —
     * they are only shared through applications (ADR 0006).
     */
    public function show(Request $request, JobseekerProfile $jobseekerProfile): Response
    {
        Gate::authorize('view', $jobseekerProfile);

        return Inertia::render('employer/talent/Show', [
            'profile' => [
                'id' => $jobseekerProfile->id,
                'full_name' => $jobseekerProfile->full_name,
                'current_title' => $jobseekerProfile->current_title,
                'skills' => $jobseekerProfile->skills,
                'experience_years' => $jobseekerProfile->experience_years,
                'country' => $jobseekerProfile->country,
                'city' => $jobseekerProfile->city,
                'phone' => $jobseekerProfile->phone,
            ],
        ]);
    }
}
