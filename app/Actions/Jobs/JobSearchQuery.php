<?php

namespace App\Actions\Jobs;

use App\Enums\JobSort;

/**
 * Pure Typesense query construction for job searches, kept separate from the
 * SearchJobs action so it stays unit-testable and the action keeps a single
 * public handle() method.
 *
 * @phpstan-import-type JobFilters from SearchJobs
 */
final class JobSearchQuery
{
    /**
     * Facet request param => Typesense document field.
     */
    private const array FACET_FIELDS = [
        'country' => 'location_country',
        'employment_type' => 'employment_type',
        'work_arrangement' => 'work_arrangement',
        'experience_level' => 'experience_level',
        'education_level' => 'education_level',
        'skills' => 'skills',
    ];

    /**
     * Documents whose embedding is farther than this cosine distance from
     * the query embedding are dropped instead of padding the results —
     * without it, hybrid search returns k-nearest neighbours for any query,
     * however unrelated. 0.68 keeps related roles (observed ~0.65 for topical
     * matches with ts/all-MiniLM-L12-v2) while dropping noise (~0.69+).
     */
    private const float VECTOR_DISTANCE_THRESHOLD = 0.68;

    /**
     * Build the full Typesense search options for a filter set. Empty
     * keyword searches match all documents by recency and skip the embedding
     * field; keyword searches run hybrid rank fusion with far semantic
     * neighbours cut off via distance_threshold.
     *
     * @param  JobFilters  $filters
     * @return array<string, mixed>
     */
    public static function options(array $filters, int $page, int $perPage): array
    {
        $keyword = trim((string) ($filters['q'] ?? ''));

        $options = [
            'facet_by' => implode(',', array_values(self::FACET_FIELDS)),
            'max_facet_values' => 30,
            'page' => $page,
            'per_page' => $perPage,
        ];

        if (($filterBy = self::filterBy($filters)) !== '') {
            $options['filter_by'] = $filterBy;
        }

        if ($keyword === '') {
            $options['query_by'] = 'title';
            $options['sort_by'] = 'created_at:desc';

            return $options;
        }

        $options['vector_query'] = sprintf(
            'embedding:([], distance_threshold: %.2f)',
            self::VECTOR_DISTANCE_THRESHOLD,
        );

        if (($filters['sort'] ?? null) === JobSort::Newest->value) {
            $options['sort_by'] = 'created_at:desc';
        }

        return $options;
    }

    /**
     * Build a Typesense filter_by expression: OR within a facet (value list),
     * AND across facets. Counts returned for a facet include its own active
     * selections (single-query behavior); multi-search facet exclusion is a
     * later enhancement.
     *
     * @param  JobFilters  $filters
     */
    public static function filterBy(array $filters): string
    {
        $clauses = [];

        foreach (self::FACET_FIELDS as $param => $field) {
            $values = array_values(array_filter((array) ($filters[$param] ?? [])));

            if ($values !== []) {
                $clauses[] = $field.':=['.implode(',', array_map(self::quote(...), $values)).']';
            }
        }

        if ($filters['currency'] ?? null) {
            $clauses[] = 'currency:='.self::quote($filters['currency']);
        }

        if (($filters['salary_min'] ?? null) !== null) {
            $clauses[] = 'salary_max:>='.(int) $filters['salary_min'];
        }

        if (($filters['salary_max'] ?? null) !== null) {
            $clauses[] = 'salary_min:<='.(int) $filters['salary_max'];
        }

        return implode(' && ', $clauses);
    }

    /**
     * Normalize a raw Typesense facet_counts payload, tolerating malformed
     * entries since it comes from an external service.
     *
     * @param  array<mixed>  $facetCounts
     * @return array<string, list<array{value: string, count: int}>>
     */
    public static function parseFacetCounts(array $facetCounts): array
    {
        $facets = [];

        foreach ($facetCounts as $facet) {
            if (! is_array($facet) || ! is_string($facet['field_name'] ?? null)) {
                continue;
            }

            $counts = [];

            foreach (is_array($facet['counts'] ?? null) ? $facet['counts'] : [] as $count) {
                if (is_array($count) && is_string($count['value'] ?? null) && is_int($count['count'] ?? null)) {
                    $counts[] = ['value' => $count['value'], 'count' => $count['count']];
                }
            }

            $facets[$facet['field_name']] = $counts;
        }

        return $facets;
    }

    /**
     * Backtick-quote a value for filter_by, stripping embedded backticks so
     * user input cannot break out of the quoted literal.
     */
    private static function quote(string $value): string
    {
        return '`'.str_replace('`', '', $value).'`';
    }
}
