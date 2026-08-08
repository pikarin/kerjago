<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Talent\SearchTalent;
use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\EmployerCapability;
use App\Enums\Gender;
use App\Enums\Language;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTalentRequest;
use App\Http\Resources\TalentDetailResource;
use App\Http\Resources\TalentSummaryResource;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use App\Support\Capabilities\EmployerCapabilities;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TalentController extends Controller
{
    /**
     * Candidates per page. Also the whole allowance for an employer without
     * the full-browse capability: one page, and no way to ask for a second.
     *
     * Cursor or numbered pagination would hide the number while still letting
     * anyone count the pool by hand, twelve at a time.
     */
    private const int RESULTS_PER_PAGE = 12;

    /**
     * Employer-facing hybrid talent search over jobseeker profiles.
     *
     * An employer without `BrowseTalentInFull` still searches, still filters
     * and still sees real (masked) candidates — the feature has to be visible
     * to be worth wanting. What they do not get is depth: one page, no facet
     * counts, and no total.
     */
    public function index(
        SearchTalentRequest $request,
        SearchTalent $searchTalent,
        ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
        EmployerCapabilities $capabilities,
    ): Response {
        $filters = $request->filters();

        /** @var User $user */
        $user = $request->user();
        /** @var EmployerProfile $employerProfile */
        $employerProfile = $user->employerProfile;

        $browseInFull = $capabilities->for($employerProfile, EmployerCapability::BrowseTalentInFull);

        // Pinned by passing the page in rather than by swapping Paginator's
        // page resolver: that resolver is process-wide static state, so under a
        // long-lived worker it would pin every later paginator in the process
        // too. Without the pin, `?page=2` walks the pool a screen at a time.
        $result = $searchTalent->handle(
            $filters,
            self::RESULTS_PER_PAGE,
            page: $browseInFull->isDenied() ? 1 : null,
        );

        $profiles = $browseInFull->allowed
            ? $result->profiles
            : $this->withoutDepth($result->profiles);

        // One lookup for the whole page of candidates; the Resource is handed
        // the answer rather than asking per card.
        $unlocked = $resolveUnlockedProfileIds->handle(
            $employerProfile,
            array_values($profiles->getCollection()
                ->map(fn (JobseekerProfile $profile): string => $profile->id)
                ->all()),
        );

        return Inertia::render('employer/talent/Index', [
            // Resource-mapped via through() to keep the flat paginator shape
            // the Vue Paginated<T> type expects (ADR 0003).
            'profiles' => $profiles->through(fn (JobseekerProfile $profile) => (new TalentSummaryResource(
                $profile,
                isset($unlocked[$profile->id]),
            ))->resolve()),
            // Cast so an empty filter set serializes as {} rather than [].
            'filters' => (object) $filters,
            // Counts are a depth signal in their own right: "240 candidates in
            // Jakarta" tells an unvetted account how deep the pool runs.
            'facets' => $browseInFull->allowed ? $result->facets : [],
            'facetsAvailable' => $browseInFull->allowed && $result->facetsAvailable,
            'browseInFull' => $browseInFull->toArray(),
            // Whether anything is actually being held back, answered here
            // because only the server still knows. The client sees a page of
            // twelve and cannot tell a pool of twelve from a pool of twelve
            // thousand — so left to guess from `data.length` it would raise the
            // wall over a search that returned everything there was, which is a
            // lie the employer can check. One bit, not the count.
            'resultsWithheld' => $browseInFull->isDenied()
                && $result->profiles->total() > $profiles->count(),
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
     * Re-wrap a page of results so the paginator describes only itself.
     *
     * The total the engine reported is the pool's depth, and it reaches the
     * client whether or not the UI prints it — as a number, and as the page
     * links derived from it. Replacing it with the number of rows actually
     * returned leaves nothing to read off.
     *
     * @param  LengthAwarePaginator<int, JobseekerProfile>  $profiles
     * @return LengthAwarePaginator<int, JobseekerProfile>
     */
    private function withoutDepth(LengthAwarePaginator $profiles): LengthAwarePaginator
    {
        $items = $profiles->getCollection();

        return (new LengthAwarePaginator(
            $items,
            $items->count(),
            self::RESULTS_PER_PAGE,
            1,
            ['path' => Paginator::resolveCurrentPath()],
        ))->withQueryString();
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
