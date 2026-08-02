<?php

use App\Admingo\Models\StaffUser;
use App\Chat\Events\MessageRead;
use App\Chat\Events\MessageSent;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Browser');

/*
|--------------------------------------------------------------------------
| Chat broadcast transport
|--------------------------------------------------------------------------
|
| Tests run against the `reverb` broadcaster because channel authorization is
| driver-dependent: the `null` and `log` drivers have an empty auth() and would
| authorize every channel, making those tests pass vacuously.
|
| The cost is that dispatching a ShouldBroadcast event tries to POST to a Reverb
| server that is not running. These two events are faked so nothing that happens
| to write a message needs one.
|
| Faked across the whole Feature suite, not just Feature/Chat: a status change or
| a job application now queues chat work, and with the sync queue that runs
| inline, so tests with no interest in chat would otherwise fail on a cURL error.
|
| Channel authorization is unaffected, because /broadcasting/auth does not go
| through the event dispatcher, and broadcastOn()/broadcastWith() are asserted
| directly against freshly constructed events.
|
*/

pest()->beforeEach(function () {
    Event::fake([MessageSent::class, MessageRead::class]);
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Browser session storage
|--------------------------------------------------------------------------
|
| The suite runs on the `array` session driver, which is per-request: the
| browser server handles every request through a fresh kernel, so an array
| session is empty again by the time a redirect arrives and no login can ever
| complete. Browser tests get the `file` driver instead, cleared per test so
| one test's session cannot leak into the next.
|
| The files go to their own directory rather than storage/framework/sessions,
| which is a tracked directory whose .gitignore would be swept away with them.
|
*/

pest()->beforeEach(function () {
    $sessionDirectory = storage_path('framework/testing/sessions');

    config([
        'session.driver' => 'file',
        'session.files' => $sessionDirectory,
    ]);

    File::deleteDirectory($sessionDirectory);
    File::ensureDirectoryExists($sessionDirectory);
})->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something(): void
{
    // ..
}

/**
 * Enrol a staff member in Admingo's app authentication, the way the panel's
 * own set-up page would, and hand back the plaintext secret so a browser test
 * can generate codes for them.
 *
 * @param  array<string, mixed>  $attributes
 * @return array{0: StaffUser, 1: string}
 */
function enrolStaffUser(array $attributes = []): array
{
    $staff = StaffUser::factory()->create($attributes);

    $provider = AppAuthentication::make();
    $provider->saveSecret($staff, $secret = $provider->generateSecret());

    return [$staff->refresh(), $secret];
}

/**
 * Assert that the page shows the given text, allowing for a Livewire round-trip
 * or a redirect still being in flight.
 *
 * `assertSee()` reads the DOM once and does not poll, so asserting straight
 * after a submit is a race that passes only while the response happens to beat
 * the next statement — and the element has to be visible at that instant, not
 * merely present, which rules out waiting on the text alone. Retrying the whole
 * assertion covers both. The last attempt is deliberately left unguarded, so a
 * genuine failure still arrives through the plugin, with its screenshot.
 */
function assertPageEventuallyShows(AwaitableWebpage|PendingAwaitablePage|Webpage $page, string $text, int $timeoutMilliseconds = 10_000): AwaitableWebpage|PendingAwaitablePage|Webpage
{
    $deadline = microtime(true) + ($timeoutMilliseconds / 1000);

    while (microtime(true) < $deadline) {
        try {
            $page->assertSee($text);

            return $page;
        } catch (Throwable) {
            usleep(100_000);
        }
    }

    $page->assertSee($text);

    return $page;
}
