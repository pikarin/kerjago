<?php

namespace App\Actions\Talent;

use App\Models\JobseekerProfile;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchTalent
{
    /**
     * Search jobseeker profiles by keyword and PRD-defined facets.
     *
     * Backed by the database for the MVP; swaps to Meilisearch behind this
     * same signature later (see ADR 0001).
     *
     * @param  array{q?: string|null, country?: string|null, city?: string|null, experience_min?: int|null}  $filters
     * @return LengthAwarePaginator<int, JobseekerProfile>
     */
    public function handle(array $filters, int $perPage = 12): LengthAwarePaginator
    {
        return JobseekerProfile::query()
            ->when($filters['q'] ?? null, function ($query, string $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->whereLike('full_name', "%{$keyword}%", caseSensitive: false)
                        ->orWhereLike('current_title', "%{$keyword}%", caseSensitive: false)
                        ->orWhereRaw('skills::text ilike ?', ["%{$keyword}%"]);
                });
            })
            ->when($filters['country'] ?? null, fn ($query, string $country) => $query->where('country', $country))
            ->when($filters['city'] ?? null, fn ($query, string $city) => $query->whereLike('city', "%{$city}%", caseSensitive: false))
            ->when($filters['experience_min'] ?? null, fn ($query, int $years) => $query->where('experience_years', '>=', $years))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
