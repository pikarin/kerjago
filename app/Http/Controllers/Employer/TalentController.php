<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Talent\SearchTalent;
use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTalentRequest;
use App\Http\Resources\TalentDetailResource;
use App\Http\Resources\TalentSummaryResource;
use App\Models\JobseekerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TalentController extends Controller
{
    /**
     * Employer-facing hybrid talent search over jobseeker profiles.
     */
    public function index(SearchTalentRequest $request, SearchTalent $searchTalent): Response
    {
        $filters = $request->filters();

        $result = $searchTalent->handle($filters);

        return Inertia::render('employer/talent/Index', [
            // Resource-mapped via through() to keep the flat paginator shape
            // the Vue Paginated<T> type expects (ADR 0003).
            'profiles' => $result->profiles->through(fn (JobseekerProfile $profile) => (new TalentSummaryResource($profile))->resolve()),
            // Cast so an empty filter set serializes as {} rather than [].
            'filters' => (object) $filters,
            'facets' => $result->facets,
            'facetsAvailable' => $result->facetsAvailable,
            'facetOptions' => [
                'experience_band' => self::experienceBandOptions(),
                'availability' => Availability::options(),
                'country' => Country::options(),
                'preferred_country' => Country::options(),
                'languages' => Language::options(),
                'education_level' => EducationLevel::options(),
                'gender' => Gender::options(),
            ],
        ]);
    }

    /**
     * A single candidate profile. Resume files are intentionally excluded —
     * they are only shared through applications (ADR 0006) — and contact
     * details stay masked until quota/consent gating ships.
     */
    public function show(Request $request, JobseekerProfile $jobseekerProfile): Response
    {
        Gate::authorize('view', $jobseekerProfile);

        $jobseekerProfile->load('workExperiences');

        return Inertia::render('employer/talent/Show', [
            'profile' => (new TalentDetailResource($jobseekerProfile))->resolve(),
        ]);
    }

    /**
     * Static value/label pairs for the experience-band facet, matching
     * JobseekerProfile::experienceBand().
     *
     * @return list<array{value: string, label: string}>
     */
    private static function experienceBandOptions(): array
    {
        return [
            ['value' => '0-1', 'label' => '0–1 years'],
            ['value' => '2-4', 'label' => '2–4 years'],
            ['value' => '5-9', 'label' => '5–9 years'],
            ['value' => '10+', 'label' => '10+ years'],
        ];
    }
}
