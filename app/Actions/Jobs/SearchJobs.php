<?php

namespace App\Actions\Jobs;

use App\Models\Job;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchJobs
{
    /**
     * Search active jobs by keyword and PRD-defined facets.
     *
     * Backed by the database for the MVP; swaps to Meilisearch behind this
     * same signature later (see ADR 0001).
     *
     * @param  array{q?: string|null, country?: string|null, currency?: string|null, salary_min?: int|null, salary_max?: int|null}  $filters
     * @return LengthAwarePaginator<int, Job>
     */
    public function handle(array $filters, int $perPage = 12): LengthAwarePaginator
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
            ->when($filters['country'] ?? null, fn ($query, string $country) => $query->where('location_country', $country))
            ->when($filters['currency'] ?? null, fn ($query, string $currency) => $query->where('currency', $currency))
            ->when($filters['salary_min'] ?? null, fn ($query, int $min) => $query->where('salary_max', '>=', $min))
            ->when($filters['salary_max'] ?? null, fn ($query, int $max) => $query->where('salary_min', '<=', $max))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
