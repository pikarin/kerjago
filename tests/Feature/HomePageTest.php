<?php

use App\Models\Job;

test('landing page shows marketplace stats and latest active jobs', function () {
    Job::factory(2)->create();
    Job::factory()->draft()->create();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->where('stats.active_jobs', 2)
            ->has('latestJobs', 2)
        );
});

test('landing page caps the latest jobs at six', function () {
    Job::factory(8)->create();

    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->has('latestJobs', 6));
});
