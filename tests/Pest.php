<?php

use App\Chat\Events\MessageRead;
use App\Chat\Events\MessageSent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
    ->in('Feature');

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
| server that is not running. These two events are faked so sending a message
| does not require one. Channel authorization is unaffected — /broadcasting/auth
| does not go through the event dispatcher — and broadcastOn()/broadcastWith()
| are asserted directly against freshly constructed events.
|
*/

pest()->beforeEach(function () {
    Event::fake([MessageSent::class, MessageRead::class]);
})->in('Feature/Chat');

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

function something()
{
    // ..
}
