<?php

namespace App\Actions\Talent;

use App\Models\JobseekerProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

/**
 * @phpstan-type TalentFilters array{q?: string|null, preferred_job_title?: list<string>|null, skills?: list<string>|null, experience_band?: list<string>|null, availability?: list<string>|null, country?: list<string>|null, city?: list<string>|null, preferred_country?: list<string>|null, preferred_city?: list<string>|null, languages?: list<string>|null, education_level?: list<string>|null, gender?: list<string>|null, experience_min?: int|null}
 */
class SearchTalent
{
    /**
     * Experience-band facet value => [min, max] years (null max = unbounded).
     */
    private const array EXPERIENCE_BANDS = [
        '0-1' => [0, 1],
        '2-4' => [2, 4],
        '5-9' => [5, 9],
        '10+' => [10, null],
    ];

    /**
     * Hybrid (keyword + semantic) search over jobseeker profiles via
     * Typesense, with per-facet counts. Falls back to a plain database query
     * when the search engine is unavailable, degrading to keyword-only
     * filtering without facet counts.
     *
     * @param  TalentFilters  $filters
     */
    public function handle(array $filters, int $perPage = 12): TalentSearchResult
    {
        if (config('scout.driver') === 'typesense') {
            try {
                return $this->searchTypesense($filters, $perPage);
            } catch (Throwable $e) {
                Log::warning('Typesense talent search failed, falling back to database.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return new TalentSearchResult($this->searchDatabase($filters, $perPage));
    }

    /**
     * @param  TalentFilters  $filters
     */
    private function searchTypesense(array $filters, int $perPage): TalentSearchResult
    {
        $keyword = trim((string) ($filters['q'] ?? ''));
        $page = Paginator::resolveCurrentPage();

        $raw = JobseekerProfile::search($keyword === '' ? '*' : $keyword)
            ->options(TalentSearchQuery::options($filters, $page, $perPage))
            ->raw();

        if (! is_array($raw)) {
            throw new UnexpectedValueException('Unexpected Typesense search response.');
        }

        $hits = is_array($raw['hits'] ?? null) ? $raw['hits'] : [];
        $found = is_int($raw['found'] ?? null) ? $raw['found'] : 0;
        $facetCounts = is_array($raw['facet_counts'] ?? null) ? $raw['facet_counts'] : [];

        $ids = [];

        foreach ($hits as $hit) {
            $document = is_array($hit) ? ($hit['document'] ?? null) : null;

            if (is_array($document) && is_string($document['id'] ?? null)) {
                $ids[] = $document['id'];
            }
        }

        $profiles = JobseekerProfile::query()
            ->with('workExperiences')
            ->findMany($ids)
            ->sortBy(fn (JobseekerProfile $profile) => array_search($profile->id, $ids, true))
            ->values();

        /** @var LengthAwarePaginator<int, JobseekerProfile> $paginator */
        $paginator = (new LengthAwarePaginator($profiles, $found, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]))->withQueryString();

        return new TalentSearchResult($paginator, TalentSearchQuery::parseFacetCounts($facetCounts), true);
    }

    /**
     * Database fallback: keyword LIKE matching and the same filters, but no
     * semantic ranking and no facet counts. Always sorted newest-first.
     *
     * @param  TalentFilters  $filters
     * @return LengthAwarePaginator<int, JobseekerProfile>
     */
    private function searchDatabase(array $filters, int $perPage): LengthAwarePaginator
    {
        return JobseekerProfile::query()
            ->with('workExperiences')
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->whereLike('full_name', "%{$keyword}%", caseSensitive: false)
                        ->orWhereLike('current_title', "%{$keyword}%", caseSensitive: false)
                        ->orWhereLike('preferred_job_title', "%{$keyword}%", caseSensitive: false)
                        ->orWhereRaw('skills::text ilike ?', ["%{$keyword}%"])
                        ->orWhereHas('workExperiences', function ($query) use ($keyword) {
                            $query->whereLike('job_title', "%{$keyword}%", caseSensitive: false);
                        });
                });
            })
            ->when($filters['preferred_job_title'] ?? null, fn ($query, array $values) => $query->whereIn('preferred_job_title', $values))
            ->when($filters['country'] ?? null, fn ($query, array $values) => $query->whereIn('country', $values))
            ->when($filters['city'] ?? null, fn ($query, array $values) => $query->whereIn('city', $values))
            ->when($filters['preferred_country'] ?? null, fn ($query, array $values) => $query->whereIn('preferred_country', $values))
            ->when($filters['preferred_city'] ?? null, fn ($query, array $values) => $query->whereIn('preferred_city', $values))
            ->when($filters['availability'] ?? null, fn ($query, array $values) => $query->whereIn('availability', $values))
            ->when($filters['gender'] ?? null, fn ($query, array $values) => $query->whereIn('gender', $values))
            ->when($filters['education_level'] ?? null, fn ($query, array $values) => $query->whereIn('education_level', $values))
            ->when($filters['skills'] ?? null, function ($query, array $skills) {
                $query->where(function ($query) use ($skills) {
                    foreach ($skills as $skill) {
                        $query->orWhereJsonContains('skills', $skill);
                    }
                });
            })
            ->when($filters['languages'] ?? null, function ($query, array $languages) {
                $query->where(function ($query) use ($languages) {
                    foreach ($languages as $language) {
                        $query->orWhereJsonContains('languages', $language);
                    }
                });
            })
            ->when($filters['experience_band'] ?? null, fn ($query, array $bands) => $this->applyExperienceBands($query, $bands))
            ->when($filters['experience_min'] ?? null, fn ($query, int $years) => $query->where('experience_years', '>=', $years))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Translate selected experience-band facet values into year ranges,
     * OR-ed together like any other facet's values.
     *
     * @param  Builder<JobseekerProfile>  $query
     * @param  list<string>  $bands
     */
    private function applyExperienceBands(Builder $query, array $bands): void
    {
        $query->where(function (Builder $query) use ($bands) {
            foreach ($bands as $band) {
                [$min, $max] = self::EXPERIENCE_BANDS[$band] ?? [null, null];

                if ($min === null) {
                    continue;
                }

                $query->orWhere(function (Builder $query) use ($min, $max) {
                    $query->where('experience_years', '>=', $min);

                    if ($max !== null) {
                        $query->where('experience_years', '<=', $max);
                    }
                });
            }
        });
    }
}
