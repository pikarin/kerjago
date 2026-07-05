<?php

use App\Enums\EmploymentType;
use App\Enums\WorkArrangement;
use App\Models\Job;

test('anyone can browse active jobs', function () {
    Job::factory(3)->create();
    Job::factory()->draft()->create();
    Job::factory()->closed()->create();

    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('jobs/Index')
            ->has('jobs.data', 3)
        );
});

test('jobs can be searched by keyword', function () {
    Job::factory()->create(['title' => 'Laravel Backend Engineer']);
    Job::factory()->create(['title' => 'Graphic Designer', 'description' => 'Design things.']);

    $this->get(route('jobs.index', ['q' => 'laravel']))
        ->assertInertia(fn ($page) => $page
            ->has('jobs.data', 1)
            ->where('jobs.data.0.title', 'Laravel Backend Engineer')
        );
});

test('jobs can be filtered by country and salary range', function () {
    Job::factory()->create([
        'location_country' => 'SG',
        'currency' => 'SGD',
        'salary_min' => 5_000,
        'salary_max' => 8_000,
    ]);
    Job::factory()->create([
        'location_country' => 'ID',
        'currency' => 'IDR',
        'salary_min' => 10_000_000,
        'salary_max' => 20_000_000,
    ]);

    $this->get(route('jobs.index', ['country' => ['SG']]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 1));

    $this->get(route('jobs.index', ['currency' => 'SGD', 'salary_min' => 6_000]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 1));

    $this->get(route('jobs.index', ['currency' => 'SGD', 'salary_min' => 9_000]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 0));
});

test('facet values combine with OR within a facet', function () {
    Job::factory()->create(['work_arrangement' => WorkArrangement::Remote]);
    Job::factory()->create(['work_arrangement' => WorkArrangement::Hybrid]);
    Job::factory()->create(['work_arrangement' => WorkArrangement::Onsite]);

    $this->get(route('jobs.index', ['work_arrangement' => ['remote', 'hybrid']]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 2));
});

test('different facets combine with AND', function () {
    Job::factory()->create([
        'employment_type' => EmploymentType::FullTime,
        'work_arrangement' => WorkArrangement::Remote,
    ]);
    Job::factory()->create([
        'employment_type' => EmploymentType::FullTime,
        'work_arrangement' => WorkArrangement::Onsite,
    ]);
    Job::factory()->create([
        'employment_type' => EmploymentType::Contract,
        'work_arrangement' => WorkArrangement::Remote,
    ]);

    $this->get(route('jobs.index', [
        'employment_type' => ['full_time'],
        'work_arrangement' => ['remote'],
    ]))->assertInertia(fn ($page) => $page->has('jobs.data', 1));
});

test('legacy jobs without facet fields appear unfiltered but are excluded by facet filters', function () {
    Job::factory()->legacy()->create();
    Job::factory()->create(['work_arrangement' => WorkArrangement::Remote]);

    $this->get(route('jobs.index'))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 2));

    $this->get(route('jobs.index', ['work_arrangement' => ['remote']]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 1));
});

test('invalid facet and sort values are rejected', function (array $query) {
    Job::factory()->create();

    $this->from(route('jobs.index'))
        ->get(route('jobs.index', $query))
        ->assertRedirect(route('jobs.index', absolute: false));
})->with([
    'bad employment type' => [['employment_type' => ['gig']]],
    'bad work arrangement' => [['work_arrangement' => ['anywhere']]],
    'bad sort' => [['sort' => 'oldest']],
]);

test('an empty filter set serializes as a JSON object, not an array', function () {
    Job::factory()->create();

    // As a JSON array, `filters.sort` would resolve to Array.prototype.sort
    // on the client and poison the next request's query string.
    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertSee('"filters":{}', escape: false);
});

test('database fallback reports facets as unavailable', function () {
    Job::factory()->create();

    $this->get(route('jobs.index'))
        ->assertInertia(fn ($page) => $page
            ->where('facetsAvailable', false)
            ->where('facets', [])
            ->has('facetOptions.employment_type')
        );
});

test('search survives an unreachable typesense server via the database fallback', function () {
    Job::factory()->create();

    config([
        'scout.driver' => 'typesense',
        'scout.typesense.client-settings.nodes.0.host' => 'host.invalid',
        'scout.typesense.client-settings.nearest_node.host' => 'host.invalid',
        'scout.typesense.client-settings.connection_timeout_seconds' => 1,
        'scout.typesense.client-settings.num_retries' => 0,
    ]);

    $this->get(route('jobs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('jobs.data', 1)
            ->where('facetsAvailable', false)
        );
});

test('an active job detail page is public', function () {
    $job = Job::factory()->create();

    $this->get(route('jobs.show', $job))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('jobs/Show')
            ->where('job.id', $job->id)
        );
});

test('draft and closed jobs are not publicly visible', function (string $state) {
    $job = Job::factory()->{$state}()->create();

    $this->get(route('jobs.show', $job))->assertNotFound();
})->with(['draft', 'closed']);
