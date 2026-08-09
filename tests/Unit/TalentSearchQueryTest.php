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
        ->and($options['query_by'])->toBe('preferred_job_title,experience_titles,skills,summary,current_company,education_institutions,preferred_location,location,embedding')
        ->and($options['query_by_weights'])->toBe('10,8,6,3,3,2,1,1,1')
        ->and($options['highlight_fields'])->toBe('preferred_job_title,experience_titles,skills,summary,current_company,education_institutions,location,preferred_location')
        ->and($options)->not->toHaveKey('sort_by')
        ->and($options['page'])->toBe(1)
        ->and($options['per_page'])->toBe(12);
});

test('options requests counts only for the fixed-option facets', function () {
    $options = TalentSearchQuery::options(['q' => 'chef'], 1, 12);

    expect($options['facet_by'])
        ->toBe('experience_band,availability,country,preferred_country,language_codes,education_level,gender');
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
        ->and($options)->not->toHaveKeys(['vector_query', 'query_by_weights', 'highlight_fields']);
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

test('parseHighlights maps matched document fields to card fields', function (array $hit, array $expected) {
    expect(TalentSearchQuery::parseHighlights($hit))->toBe($expected);
})->with([
    'no highlight data' => [
        ['document' => ['id' => 'x']],
        [],
    ],
    'highlight object keys' => [
        ['highlight' => [
            'preferred_job_title' => ['snippet' => 'Sous <mark>Chef</mark>'],
            'summary' => ['snippet' => '…<mark>chef</mark>…'],
        ]],
        ['preferred_job_title', 'summary'],
    ],
    'work-experience titles tint the current title' => [
        ['highlight' => ['experience_titles' => [['snippet' => '<mark>Chef</mark>']]]],
        ['current_title'],
    ],
    'education institutions tint the education field' => [
        ['highlight' => ['education_institutions' => [['snippet' => '<mark>ITB</mark>']]]],
        ['education_level'],
    ],
    'unknown and embedding fields are dropped' => [
        ['highlight' => [
            'embedding' => [],
            'full_name' => ['snippet' => 'leak'],
            'skills' => [['snippet' => '<mark>PHP</mark>']],
        ]],
        ['skills'],
    ],
    'legacy highlights array is the fallback' => [
        ['highlights' => [
            ['field' => 'current_company', 'snippet' => '<mark>Kerjago</mark>'],
            ['field' => 'not_a_field', 'snippet' => 'x'],
        ]],
        ['current_company'],
    ],
    'highlight object wins over the legacy array without duplicating' => [
        ['highlight' => ['skills' => []], 'highlights' => [['field' => 'skills'], ['field' => 'summary']]],
        ['skills'],
    ],
]);

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
