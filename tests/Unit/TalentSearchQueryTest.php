<?php

use App\Actions\Talent\TalentSearchQuery;

test('filterBy builds typesense expressions', function (array $filters, string $expected) {
    expect(TalentSearchQuery::filterBy($filters))->toBe($expected);
})->with([
    'empty' => [[], ''],
    'single facet value' => [
        ['availability' => ['immediately']],
        'availability:=[`immediately`]',
    ],
    'multiple values are OR within a facet' => [
        ['availability' => ['immediately', 'two_weeks']],
        'availability:=[`immediately`,`two_weeks`]',
    ],
    'multiple facets are AND' => [
        ['skills' => ['PHP'], 'country' => ['ID']],
        'skills:=[`PHP`] && country:=[`ID`]',
    ],
    'experience band is a string facet' => [
        ['experience_band' => ['2-4', '10+']],
        'experience_band:=[`2-4`,`10+`]',
    ],
    'experience_min becomes a numeric clause' => [
        ['experience_min' => 5],
        'experience_years:>=5',
    ],
    'backticks are stripped from values' => [
        ['skills' => ['Vue`.js']],
        'skills:=[`Vue.js`]',
    ],
    'empty arrays are ignored' => [
        ['country' => [], 'skills' => ['PHP']],
        'skills:=[`PHP`]',
    ],
]);

test('options runs weighted hybrid search for keyword queries', function () {
    $options = TalentSearchQuery::options(['q' => 'chef'], 1, 12);

    expect($options['vector_query'])->toBe('embedding:([], distance_threshold: 0.68)')
        ->and($options['query_by'])->toBe('preferred_job_title,experience_titles,skills,preferred_location,location,embedding')
        ->and($options['query_by_weights'])->toBe('8,6,4,2,1,1')
        ->and($options)->not->toHaveKey('sort_by')
        ->and($options['page'])->toBe(1)
        ->and($options['per_page'])->toBe(12);
});

test('query_by and query_by_weights have matching field counts', function () {
    // A count mismatch is a Typesense 400 at request time.
    $options = TalentSearchQuery::options(['q' => 'chef'], 1, 12);

    expect(count(explode(',', (string) $options['query_by'])))
        ->toBe(count(explode(',', (string) $options['query_by_weights'])));
});

test('options matches all by recency without a keyword and skips the embedding field', function (array $filters) {
    $options = TalentSearchQuery::options($filters, 1, 12);

    expect($options['query_by'])->toBe('preferred_job_title')
        ->and($options['sort_by'])->toBe('created_at:desc')
        ->and($options)->not->toHaveKeys(['vector_query', 'query_by_weights']);
})->with([
    'no filters' => [[]],
    'blank keyword' => [['q' => '  ']],
    'facets only' => [['availability' => ['immediately']]],
]);

test('options includes filter_by only when filters produce clauses', function () {
    expect(TalentSearchQuery::options([], 1, 12))->not->toHaveKey('filter_by')
        ->and(TalentSearchQuery::options(['country' => ['SG']], 1, 12)['filter_by'])
        ->toBe('country:=[`SG`]');
});

test('parseFacetCounts normalizes the typesense payload', function () {
    $raw = [
        [
            'field_name' => 'availability',
            'counts' => [
                ['value' => 'immediately', 'count' => 4, 'highlighted' => 'immediately'],
            ],
        ],
    ];

    expect(TalentSearchQuery::parseFacetCounts($raw))->toBe([
        'availability' => [
            ['value' => 'immediately', 'count' => 4],
        ],
    ]);
});
