<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Talent\SearchTalent;
use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTalentRequest;
use App\Http\Resources\TalentDetailResource;
use App\Http\Resources\TalentSummaryResource;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TalentController extends Controller
{
    /**
     * Employer-facing hybrid talent search over jobseeker profiles.
     */
    public function index(
        SearchTalentRequest $request,
        SearchTalent $searchTalent,
        ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
    ): Response {
        $filters = $request->filters();

        $result = $searchTalent->handle($filters);

        /** @var User $user */
        $user = $request->user();
        /** @var EmployerProfile $employerProfile */
        $employerProfile = $user->employerProfile;

        // One lookup for the whole page of candidates; the Resource is handed
        // the answer rather than asking per card.
        $unlocked = $resolveUnlockedProfileIds->handle(
            $employerProfile,
            array_values($result->profiles->getCollection()
                ->map(fn (JobseekerProfile $profile): string => $profile->id)
                ->all()),
        );

        return Inertia::render('employer/talent/Index', [
            // Resource-mapped via through() to keep the flat paginator shape
            // the Vue Paginated<T> type expects (ADR 0003).
            'profiles' => $result->profiles->through(fn (JobseekerProfile $profile) => (new TalentSummaryResource(
                $profile,
                isset($unlocked[$profile->id]),
            ))->resolve()),
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
     * they are only shared through applications (ADR 0006) — and name, email
     * and phone stay masked unless the employer holds an active unlock.
     */
    public function show(
        Request $request,
        JobseekerProfile $jobseekerProfile,
        ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
    ): Response {
        Gate::authorize('view', $jobseekerProfile);

        $jobseekerProfile->load(['user:id,email', 'workExperiences', 'educations', 'languages']);

        /** @var User $user */
        $user = $request->user();
        /** @var EmployerProfile $employerProfile */
        $employerProfile = $user->employerProfile;

        return Inertia::render('employer/talent/Show', [
            'profile' => (new TalentDetailResource(
                $jobseekerProfile,
                $resolveUnlockedProfileIds->has($employerProfile, $jobseekerProfile->id),
            ))->resolve(),
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
