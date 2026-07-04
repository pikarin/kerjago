<?php

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

    $this->get(route('jobs.index', ['country' => 'SG']))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 1));

    $this->get(route('jobs.index', ['currency' => 'SGD', 'salary_min' => 6_000]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 1));

    $this->get(route('jobs.index', ['currency' => 'SGD', 'salary_min' => 9_000]))
        ->assertInertia(fn ($page) => $page->has('jobs.data', 0));
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
