<?php

namespace App\Jobs;

use App\Actions\Chat\EnsureApplicationConversation;
use App\Chat\Actions\PostSystemMessage;
use App\Chat\Enums\MessageType;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Puts a status change inline in the conversation, so an employer manages an
 * applicant in one place instead of switching between a chat and a status
 * dropdown.
 *
 * Queued for the same reason as OpenApplicationConversation: changing a status
 * must not be able to fail because chat did.
 */
class AnnounceApplicationStatusChange implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Exponential backoff so a transient database or Redis blip does not burn
     * all three attempts in the same second.
     *
     * @var list<int>
     */
    public array $backoff = [1, 5, 10];

    public function __construct(
        public Application $application,
        public ApplicationStatus $status,
    ) {}

    public function handle(
        EnsureApplicationConversation $ensureApplicationConversation,
        PostSystemMessage $postSystemMessage,
    ): void {
        // Ensuring is idempotent, and an application created before chat existed
        // has no conversation yet.
        $conversation = $ensureApplicationConversation->handle($this->application);

        $body = __('Application status changed to :status.', ['status' => $this->status->value]);

        // Posting a system message is an unconditional insert, so this job is
        // only safe to retry with a guard. An identical *consecutive*
        // announcement is never a real transition — it is either a retry after
        // the insert already succeeded, or an employer re-selecting the status
        // the application is already in. Both should be silent.
        $latestAnnouncement = $conversation->messages()
            ->where('type', MessageType::System)
            ->orderByDesc('id')
            ->value('body');

        if ($latestAnnouncement === $body) {
            return;
        }

        $postSystemMessage->handle($conversation, $body);
    }

    public function failed(?Throwable $exception): void
    {
        // Without this the application's status change succeeds and the chat
        // side vanishes into failed_jobs with nothing pointing at it.
        Log::error('Could not announce an application status change in chat.', [
            'application_id' => $this->application->id,
            'status' => $this->status->value,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
