<?php

use App\Actions\Jobs\JobSearchQuery;

test('filterBy builds typesense expressions', function (array $filters, string $expected) {
    expect(JobSearchQuery::filterBy($filters))->toBe($expected);
})->with([
    'empty' => [[], ''],
    'single facet value' => [
        ['work_arrangement' => ['remote']],
        'work_arrangement:=[`remote`]',
    ],
    'multiple values are OR within a facet' => [
        ['work_arrangement' => ['remote', 'hybrid']],
        'work_arrangement:=[`remote`,`hybrid`]',
    ],
    'multiple facets are AND' => [
        ['employment_type' => ['full_time'], 'work_arrangement' => ['remote']],
        'employment_type:=[`full_time`] && work_arrangement:=[`remote`]',
    ],
    'country maps to location_country' => [
        ['country' => ['SG', 'ID']],
        'location_country:=[`SG`,`ID`]',
    ],
    'currency and overlapping salary range' => [
        ['currency' => 'SGD', 'salary_min' => 5000, 'salary_max' => 9000],
        'currency:=`SGD` && salary_max:>=5000 && salary_min:<=9000',
    ],
    'backticks are stripped from skill values' => [
        ['skills' => ['Vue`.js']],
        'skills:=[`Vue.js`]',
    ],
    'backticks are stripped from currency' => [
        ['currency' => 'SG`D'],
        'currency:=`SGD`',
    ],
    'empty arrays are ignored' => [
        ['country' => [], 'skills' => ['PHP']],
        'skills:=[`PHP`]',
    ],
]);

test('options runs hybrid search with a vector distance cutoff for keyword queries', function () {
    $options = JobSearchQuery::options(['q' => 'laravel'], 1, 12);

    expect($options['vector_query'])->toBe('embedding:([], distance_threshold: 0.68)')
        ->and($options)->not->toHaveKeys(['sort_by', 'query_by'])
        ->and($options['page'])->toBe(1)
        ->and($options['per_page'])->toBe(12);
});

test('options sorts keyword queries by recency when newest is requested', function () {
    $options = JobSearchQuery::options(['q' => 'laravel', 'sort' => 'newest'], 2, 12);

    expect($options['sort_by'])->toBe('created_at:desc')
        ->and($options['vector_query'])->toBe('embedding:([], distance_threshold: 0.68)')
        ->and($options['page'])->toBe(2);
});

test('options matches all by recency without a keyword and skips the embedding field', function (array $filters) {
    $options = JobSearchQuery::options($filters, 1, 12);

    expect($options['query_by'])->toBe('title')
        ->and($options['sort_by'])->toBe('created_at:desc')
        ->and($options)->not->toHaveKey('vector_query');
})->with([
    'no filters' => [[]],
    'blank keyword' => [['q' => '  ']],
    'facets only' => [['work_arrangement' => ['remote']]],
]);

test('options includes filter_by only when filters produce clauses', function () {
    expect(JobSearchQuery::options([], 1, 12))->not->toHaveKey('filter_by')
        ->and(JobSearchQuery::options(['country' => ['SG']], 1, 12)['filter_by'])
        ->toBe('location_country:=[`SG`]');
});

test('parseFacetCounts normalizes the typesense payload', function () {
    $raw = [
        [
            'field_name' => 'work_arrangement',
            'counts' => [
                ['value' => 'remote', 'count' => 23, 'highlighted' => 'remote'],
                ['value' => 'onsite', 'count' => 7, 'highlighted' => 'onsite'],
            ],
        ],
        [
            'field_name' => 'skills',
            'counts' => [],
        ],
    ];

    expect(JobSearchQuery::parseFacetCounts($raw))->toBe([
        'work_arrangement' => [
            ['value' => 'remote', 'count' => 23],
            ['value' => 'onsite', 'count' => 7],
        ],
        'skills' => [],
    ]);
});

test('parseFacetCounts tolerates malformed payload entries', function () {
    expect(JobSearchQuery::parseFacetCounts([]))->toBe([])
        ->and(JobSearchQuery::parseFacetCounts([
            'not an array entry',
            ['counts' => [['value' => 'x', 'count' => 1]]],
            ['field_name' => 'skills', 'counts' => [['value' => 'PHP', 'count' => 'NaN']]],
        ]))->toBe(['skills' => []]);
});
