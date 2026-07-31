<?php

use App\Chat\Contracts\ContextResolver;
use App\Enums\ChatContextType;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Str;

function contextResolver(): ContextResolver
{
    return app(ContextResolver::class);
}

test('a job context resolves to its title and public url', function () {
    $job = Job::factory()->create(['title' => 'Senior Laravel Engineer']);

    $resolved = contextResolver()->resolve(ChatContextType::Job->value, [$job->id]);

    expect($resolved[$job->id]->label)->toBe('Senior Laravel Engineer')
        ->and($resolved[$job->id]->url)->toBe(route('jobs.show', $job))
        ->and($resolved[$job->id]->isPlaceholder)->toBeFalse();
});

test('an application context is labelled by the job applied to', function () {
    $job = Job::factory()->create(['title' => 'Product Designer']);
    $application = Application::factory()->for($job)->create();

    $resolved = contextResolver()->resolve(ChatContextType::Application->value, [$application->id]);

    expect($resolved[$application->id]->label)->toBe('Product Designer');
});

/**
 * The consequence of holding no foreign key: the host record can disappear
 * while the conversation remains. Rendering must degrade, not fail.
 */
test('a deleted job yields a placeholder instead of throwing', function () {
    $job = Job::factory()->create();
    $id = $job->id;
    $job->delete();

    $resolved = contextResolver()->resolve(ChatContextType::Job->value, [$id]);

    expect($resolved)->toHaveKey($id)
        ->and($resolved[$id]->isPlaceholder)->toBeTrue()
        ->and($resolved[$id]->label)->not->toBeEmpty();
});

/**
 * Chat may hold context written by a different version of this application.
 * The contract says tolerate it.
 */
test('an unrecognised context type does not throw', function () {
    $id = (string) Str::ulid();

    $resolved = contextResolver()->resolve('something_invented_later', [$id]);

    expect($resolved[$id]->isPlaceholder)->toBeTrue()
        ->and($resolved[$id]->type)->toBe('something_invented_later');
});

test('resolving no ids returns an empty map', function () {
    expect(contextResolver()->resolve(ChatContextType::Job->value, []))->toBe([]);
});
