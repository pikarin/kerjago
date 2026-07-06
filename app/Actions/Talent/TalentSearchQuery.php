<?php

namespace App\Actions\Talent;

use App\Actions\Jobs\JobSearchQuery;

/**
 * Pure Typesense query construction for talent searches, kept separate from
 * the SearchTalent action so it stays unit-testable and the action keeps a
 * single public handle() method.
 *
 * @phpstan-import-type TalentFilters from SearchTalent
 */
final class TalentSearchQuery
{
    /**
     * Text fields matched by a free-text query, in descending relevance
     * order, plus the embedding field that turns the search hybrid.
     */
    private const string QUERY_BY = 'preferred_job_title,experience_titles,skills,preferred_location,location,embedding';

    /**
     * One weight per QUERY_BY field — Typesense rejects the request when the
     * counts differ. The embedding weight is ignored (the vector leg is
     * fused via rank fusion) but a placeholder is still required.
     */
    private const string QUERY_BY_WEIGHTS = '8,6,4,2,1,1';

    /**
     * Facet request param => Typesense document field.
     */
    private const array FACET_FIELDS = [
        'preferred_job_title' => 'preferred_job_title',
        'skills' => 'skills',
        'experience_band' => 'experience_band',
        'availability' => 'availability',
        'country' => 'country',
        'city' => 'city',
        'preferred_country' => 'preferred_country',
        'preferred_city' => 'preferred_city',
        'languages' => 'languages',
        'education_level' => 'education_level',
        'gender' => 'gender',
    ];

    /**
     * Same starting point as jobs (same embedding model); profile documents
     * are sparser than job posts, so retune independently if recall feels
     * low in practice.
     */
    private const float VECTOR_DISTANCE_THRESHOLD = 0.68;

    /**
     * Build the full Typesense search options for a filter set. Empty
     * keyword searches match all documents by recency and skip the embedding
     * field; keyword searches run hybrid rank fusion with far semantic
     * neighbours cut off via distance_threshold.
     *
     * @param  TalentFilters  $filters
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
            $options['query_by'] = 'preferred_job_title';
            $options['sort_by'] = 'created_at:desc';

            return $options;
        }

        $options['query_by'] = self::QUERY_BY;
        $options['query_by_weights'] = self::QUERY_BY_WEIGHTS;
        $options['vector_query'] = sprintf(
            'embedding:([], distance_threshold: %.2f)',
            self::VECTOR_DISTANCE_THRESHOLD,
        );

        return $options;
    }

    /**
     * Build a Typesense filter_by expression: OR within a facet (value list),
     * AND across facets. Counts returned for a facet include its own active
     * selections (single-query behavior); multi-search facet exclusion is a
     * later enhancement.
     *
     * @param  TalentFilters  $filters
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

        if (($filters['experience_min'] ?? null) !== null) {
            $clauses[] = 'experience_years:>='.(int) $filters['experience_min'];
        }

        return implode(' && ', $clauses);
    }

    /**
     * Normalize a raw Typesense facet_counts payload.
     *
     * @param  array<mixed>  $facetCounts
     * @return array<string, list<array{value: string, count: int}>>
     */
    public static function parseFacetCounts(array $facetCounts): array
    {
        return JobSearchQuery::parseFacetCounts($facetCounts);
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
