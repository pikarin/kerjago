<?php

namespace App\Jobs;

use App\Actions\Chat\EnsureApplicationConversation;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued so that applying to a job never depends on chat succeeding.
 *
 * This is the isolation, not a try/catch: the controller's success path does not
 * execute chat code at all, so a chat outage delays a conversation appearing and
 * nothing more.
 */
class OpenApplicationConversation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Safe to retry because EnsureApplicationConversation converges on one
     * conversation: OpenConversation enforces at-most-one through the
     * unique_key index rather than a read-then-write check.
     *
     * @var list<int>
     */
    public array $backoff = [1, 5, 10];

    public function __construct(public Application $application) {}

    public function handle(EnsureApplicationConversation $ensureApplicationConversation): void
    {
        $ensureApplicationConversation->handle($this->application);
    }

    public function failed(?Throwable $exception): void
    {
        // The application itself is already committed. Without this line a
        // jobseeker applies, the chat leg fails, and there is simply no
        // conversation and no record of why.
        Log::error('Could not open a conversation for an application.', [
            'application_id' => $this->application->id,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
