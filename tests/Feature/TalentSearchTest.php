<?php

use App\Enums\Availability;
use App\Enums\Gender;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use App\Models\WorkExperience;

test('employer with a company profile can browse jobseeker profiles', function () {
    JobseekerProfile::factory(3)->create();

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('employer/talent/Index')
            ->has('profiles.data', 3)
        );
});

test('employer without a company profile is redirected to profile setup', function () {
    $this->actingAs(User::factory()->employer()->create())
        ->get(route('employer.talent.index'))
        ->assertRedirect(route('employer.profile.edit', absolute: false));
});

test('jobseeker cannot access talent search', function () {
    $this->actingAs(User::factory()->jobseeker()->create())
        ->get(route('employer.talent.index'))
        ->assertForbidden();
});

test('guests are redirected to login from talent search', function () {
    $this->get(route('employer.talent.index'))->assertRedirect(route('login', absolute: false));
});

test('talent can be searched by keyword across name, titles, and skills', function () {
    JobseekerProfile::factory()->minimal()->create(['full_name' => 'Andi Wijaya', 'current_title' => 'Chef', 'skills' => ['Cooking']]);
    JobseekerProfile::factory()->minimal()->create(['full_name' => 'Budi Santoso', 'current_title' => 'Laravel Developer', 'skills' => ['PHP']]);
    JobseekerProfile::factory()->minimal()->create(['full_name' => 'Citra Dewi', 'current_title' => 'Designer', 'skills' => ['Laravel', 'Vue.js']]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['q' => 'laravel']))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 2));
});

test('talent can be searched by preferred job title and work-experience titles', function () {
    JobseekerProfile::factory()->minimal()->create(['full_name' => 'Andi', 'current_title' => 'Chef', 'skills' => ['Cooking']]);
    JobseekerProfile::factory()->minimal()->create([
        'full_name' => 'Budi',
        'current_title' => 'Sous Chef',
        'skills' => ['Cooking'],
        'preferred_job_title' => 'Restaurant Manager',
    ]);
    $pastManager = JobseekerProfile::factory()->minimal()->create(['full_name' => 'Citra', 'current_title' => 'Chef', 'skills' => ['Cooking']]);
    WorkExperience::factory()->for($pastManager, 'jobseekerProfile')->create(['job_title' => 'Kitchen Manager']);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['q' => 'manager']))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 2));
});

test('talent can be filtered by country and experience', function () {
    JobseekerProfile::factory()->create(['country' => 'ID', 'experience_years' => 2]);
    JobseekerProfile::factory()->create(['country' => 'ID', 'experience_years' => 8]);
    JobseekerProfile::factory()->create(['country' => 'VN', 'experience_years' => 10]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['country' => ['ID'], 'experience_min' => 5]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 1));
});

test('scalar filter values from old bookmarked urls are still accepted', function () {
    JobseekerProfile::factory()->create(['country' => 'ID']);
    JobseekerProfile::factory()->create(['country' => 'VN']);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['country' => 'ID']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('profiles.data', 1));
});

test('facet values combine with OR within a facet and AND across facets', function () {
    JobseekerProfile::factory()->create(['availability' => Availability::Immediately, 'country' => 'ID']);
    JobseekerProfile::factory()->create(['availability' => Availability::TwoWeeks, 'country' => 'ID']);
    JobseekerProfile::factory()->create(['availability' => Availability::Immediately, 'country' => 'VN']);
    JobseekerProfile::factory()->create(['availability' => Availability::TwoMonthsPlus, 'country' => 'ID']);

    $employer = EmployerProfile::factory()->create()->user;

    $this->actingAs($employer)
        ->get(route('employer.talent.index', ['availability' => ['immediately', 'two_weeks']]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 3));

    $this->actingAs($employer)
        ->get(route('employer.talent.index', [
            'availability' => ['immediately', 'two_weeks'],
            'country' => ['ID'],
        ]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 2));
});

test('talent can be filtered by skills and languages', function () {
    JobseekerProfile::factory()->create(['skills' => ['PHP', 'Laravel'], 'languages' => ['id', 'en']]);
    JobseekerProfile::factory()->create(['skills' => ['Figma'], 'languages' => ['th']]);

    $employer = EmployerProfile::factory()->create()->user;

    $this->actingAs($employer)
        ->get(route('employer.talent.index', ['skills' => ['Laravel']]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 1));

    $this->actingAs($employer)
        ->get(route('employer.talent.index', ['languages' => ['en', 'th']]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 2));
});

test('experience bands translate to year ranges on the database fallback', function () {
    JobseekerProfile::factory()->create(['experience_years' => 1]);
    JobseekerProfile::factory()->create(['experience_years' => 3]);
    JobseekerProfile::factory()->create(['experience_years' => 7]);
    JobseekerProfile::factory()->create(['experience_years' => 12]);

    $employer = EmployerProfile::factory()->create()->user;

    $this->actingAs($employer)
        ->get(route('employer.talent.index', ['experience_band' => ['2-4']]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 1));

    $this->actingAs($employer)
        ->get(route('employer.talent.index', ['experience_band' => ['0-1', '10+']]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 2));
});

test('talent can be filtered by preferred location', function () {
    JobseekerProfile::factory()->create(['preferred_country' => 'SG']);
    JobseekerProfile::factory()->create(['preferred_country' => 'ID']);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index', ['preferred_country' => ['SG']]))
        ->assertInertia(fn ($page) => $page->has('profiles.data', 1));
});

test('invalid facet values are rejected', function (array $query) {
    JobseekerProfile::factory()->create();

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->from(route('employer.talent.index'))
        ->get(route('employer.talent.index', $query))
        ->assertRedirect(route('employer.talent.index', absolute: false));
})->with([
    'bad availability' => [['availability' => ['someday']]],
    'bad experience band' => [['experience_band' => ['20+']]],
    'bad gender' => [['gender' => ['unknown']]],
    'bad language' => [['languages' => ['fr']]],
]);

test('an empty filter set serializes as a JSON object, not an array', function () {
    JobseekerProfile::factory()->create();

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertOk()
        ->assertSee('"filters":{}', escape: false);
});

test('database fallback reports facets as unavailable', function () {
    JobseekerProfile::factory()->create();

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertInertia(fn ($page) => $page
            ->where('facetsAvailable', false)
            ->where('facets', [])
            ->has('facetOptions.availability')
            ->has('facetOptions.experience_band')
        );
});

test('talent search survives an unreachable typesense server via the database fallback', function () {
    JobseekerProfile::factory()->create();

    config([
        'scout.driver' => 'typesense',
        'scout.typesense.client-settings.nodes.0.host' => 'host.invalid',
        'scout.typesense.client-settings.nearest_node.host' => 'host.invalid',
        'scout.typesense.client-settings.connection_timeout_seconds' => 1,
        'scout.typesense.client-settings.num_retries' => 0,
    ]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('profiles.data', 1)
            ->where('facetsAvailable', false)
        );
});

test('search results never expose contact details', function () {
    JobseekerProfile::factory()->create(['phone' => '+6281234567890']);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.index'))
        ->assertInertia(fn ($page) => $page
            ->has('profiles.data', 1)
            ->missing('profiles.data.0.phone')
        );
});

test('search documents always carry the embedding source fields, with fallbacks', function () {
    // Typesense fails to embed a document when an embed.from source field is
    // missing, so minimal profiles must fall back to current-role data.
    $minimal = JobseekerProfile::factory()->minimal()->create([
        'current_title' => 'Chef',
        'city' => 'Jakarta',
        'country' => 'ID',
    ]);

    $document = $minimal->toSearchableArray();

    expect($document['preferred_job_title'])->toBe('Chef')
        ->and($document['experience_titles'])->toBe(['Chef'])
        ->and($document['preferred_location'])->toBe('Jakarta, Indonesia')
        ->and($document['location'])->toBe('Jakarta, Indonesia')
        ->and($document)->not->toHaveKeys(['availability', 'gender', 'education_level', 'languages', 'preferred_country', 'preferred_city']);

    $full = JobseekerProfile::factory()->create([
        'preferred_job_title' => 'Head Chef',
        'preferred_city' => 'Singapore',
        'preferred_country' => 'SG',
    ]);
    WorkExperience::factory()->for($full, 'jobseekerProfile')->create(['job_title' => 'Line Cook']);

    $document = $full->refresh()->toSearchableArray();

    expect($document['preferred_job_title'])->toBe('Head Chef')
        ->and($document['experience_titles'])->toBe(['Line Cook'])
        ->and($document['preferred_location'])->toBe('Singapore, Singapore');
});

test('experience years bucket into the expected bands', function (int $years, string $band) {
    $profile = JobseekerProfile::factory()->make(['experience_years' => $years]);

    expect($profile->experienceBand())->toBe($band);
})->with([
    [0, '0-1'],
    [1, '0-1'],
    [2, '2-4'],
    [4, '2-4'],
    [5, '5-9'],
    [9, '5-9'],
    [10, '10+'],
    [30, '10+'],
]);

test('employer can view a candidate profile without contact details', function () {
    $profile = JobseekerProfile::factory()->withExperience()->create([
        'phone' => '+6281234567890',
        'gender' => Gender::Female,
    ]);

    $this->actingAs(EmployerProfile::factory()->create()->user)
        ->get(route('employer.talent.show', $profile))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('employer/talent/Show')
            ->where('profile.id', $profile->id)
            ->missing('profile.phone')
            ->has('profile.work_experiences', 2)
        );
});
