<?php

use App\Chat\Contracts\ParticipantResolver;
use App\Models\EmployerProfile;
use App\Models\JobseekerProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function resolver(): ParticipantResolver
{
    return app(ParticipantResolver::class);
}

/**
 * Counts queries for one resolve() call.
 *
 * @param  list<string>  $ids
 */
function queriesToResolve(array $ids): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    resolver()->resolve($ids);

    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

test('an employer resolves to their company name', function () {
    $profile = EmployerProfile::factory()->verified()->create(['company_name' => 'Kerjago Labs']);

    $resolved = resolver()->resolve([$profile->user_id]);

    expect($resolved[$profile->user_id]->name)->toBe('Kerjago Labs')
        ->and($resolved[$profile->user_id]->isPlaceholder)->toBeFalse();
});

test('a jobseeker resolves to their professional name', function () {
    $profile = JobseekerProfile::factory()->create(['full_name' => 'Sri Wahyuni']);

    $resolved = resolver()->resolve([$profile->user_id]);

    expect($resolved[$profile->user_id]->name)->toBe('Sri Wahyuni');
});

test('a user without a profile falls back to their account name', function () {
    $user = User::factory()->jobseeker()->create(['name' => 'No Profile Yet']);

    expect(resolver()->resolve([$user->id])[$user->id]->name)->toBe('No Profile Yet');
});

/**
 * Chat stores participant ids with no foreign key, so an id can outlive the
 * account. The contract requires an entry for every requested id.
 */
test('an unresolvable id yields a placeholder rather than a missing key', function () {
    $ghost = (string) Str::ulid();
    $real = User::factory()->create();

    $resolved = resolver()->resolve([$ghost, $real->id]);

    expect($resolved)->toHaveKeys([$ghost, $real->id])
        ->and($resolved[$ghost]->isPlaceholder)->toBeTrue()
        ->and($resolved[$ghost]->id)->toBe($ghost)
        ->and($resolved[$ghost]->name)->not->toBeEmpty()
        ->and($resolved[$real->id]->isPlaceholder)->toBeFalse();
});

test('resolving nothing touches the database at all', function () {
    expect(queriesToResolve([]))->toBe(0);
});

/**
 * The load-bearing guard. A per-id resolver looks fine inside the monolith and
 * becomes one network round-trip per message once chat is extracted, so what
 * matters is not the absolute query count but that it does not grow with N.
 */
test('query count does not grow with the number of participants', function () {
    $few = User::factory()->count(2)->create()->pluck('id')->all();
    $many = User::factory()->count(25)->create()->pluck('id')->all();

    expect(queriesToResolve($many))->toBe(queriesToResolve($few));
});
