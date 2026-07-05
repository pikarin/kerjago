<?php

namespace App\Actions\Jobs;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Throwable;
use UnexpectedValueException;

/**
 * @phpstan-type JobFilters array{q?: string|null, country?: list<string>|null, employment_type?: list<string>|null, work_arrangement?: list<string>|null, experience_level?: list<string>|null, education_level?: list<string>|null, skills?: list<string>|null, currency?: string|null, salary_min?: int|null, salary_max?: int|null, sort?: string|null}
 */
class SearchJobs
{
    /**
     * Hybrid (keyword + semantic) search over active jobs via Typesense,
     * with per-facet counts. Falls back to a plain database query when the
     * search engine is unavailable, degrading to keyword-only filtering
     * without facet counts.
     *
     * @param  JobFilters  $filters
     */
    public function handle(array $filters, int $perPage = 12): JobSearchResult
    {
        if (config('scout.driver') === 'typesense') {
            try {
                return $this->searchTypesense($filters, $perPage);
            } catch (Throwable $e) {
                Log::warning('Typesense job search failed, falling back to database.', [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return new JobSearchResult($this->searchDatabase($filters, $perPage));
    }

    /**
     * @param  JobFilters  $filters
     */
    private function searchTypesense(array $filters, int $perPage): JobSearchResult
    {
        $keyword = trim((string) ($filters['q'] ?? ''));
        $page = Paginator::resolveCurrentPage();

        $raw = Job::search($keyword === '' ? '*' : $keyword)
            ->options(JobSearchQuery::options($filters, $page, $perPage))
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

        $jobs = Job::query()
            ->with('employerProfile')
            ->findMany($ids)
            ->sortBy(fn (Job $job) => array_search($job->id, $ids, true))
            ->values();

        /** @var LengthAwarePaginator<int, Job> $paginator */
        $paginator = (new LengthAwarePaginator($jobs, $found, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]))->withQueryString();

        return new JobSearchResult($paginator, JobSearchQuery::parseFacetCounts($facetCounts), true);
    }

    /**
     * Database fallback: keyword LIKE matching and the same filters, but no
     * semantic ranking and no facet counts. Always sorted newest-first.
     *
     * @param  JobFilters  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    private function searchDatabase(array $filters, int $perPage): LengthAwarePaginator
    {
        return Job::query()
            ->active()
            ->with('employerProfile')
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->whereLike('title', "%{$keyword}%", caseSensitive: false)
                        ->orWhereLike('description', "%{$keyword}%", caseSensitive: false)
                        ->orWhereLike('location_city', "%{$keyword}%", caseSensitive: false);
                });
            })
            ->when($filters['country'] ?? null, fn ($query, array $countries) => $query->whereIn('location_country', $countries))
            ->when($filters['employment_type'] ?? null, fn ($query, array $values) => $query->whereIn('employment_type', $values))
            ->when($filters['work_arrangement'] ?? null, fn ($query, array $values) => $query->whereIn('work_arrangement', $values))
            ->when($filters['education_level'] ?? null, fn ($query, array $values) => $query->whereIn('education_level', $values))
            ->when($filters['experience_level'] ?? null, fn ($query, array $values) => $query->whereIn('experience_level', $values))
            ->when($filters['skills'] ?? null, function ($query, array $skills) {
                $query->where(function ($query) use ($skills) {
                    foreach ($skills as $skill) {
                        $query->orWhereJsonContains('skills', $skill);
                    }
                });
            })
            ->when($filters['currency'] ?? null, fn ($query, string $currency) => $query->where('currency', $currency))
            ->when($filters['salary_min'] ?? null, fn ($query, int $min) => $query->where('salary_max', '>=', $min))
            ->when($filters['salary_max'] ?? null, fn ($query, int $max) => $query->where('salary_min', '<=', $max))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
