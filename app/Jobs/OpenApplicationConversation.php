<?php

namespace App\Jobs;

use App\Actions\Chat\EnsureApplicationConversation;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued so that applying to a job never depends on chat succeeding.
 *
 * This is the isolation, not a try/catch: the controller's success path does not
 * execute chat code at all, so a chat outage delays a conversation appearing and
 * nothing more. The job is idempotent, so a retry is safe.
 */
class OpenApplicationConversation implements ShouldQueue
{
    use Queueable;

    public function __construct(public Application $application) {}

    public function handle(EnsureApplicationConversation $ensureApplicationConversation): void
    {
        $ensureApplicationConversation->handle($this->application);
    }
}
