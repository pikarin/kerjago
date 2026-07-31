<?php

namespace App\Jobs;

use App\Actions\Chat\EnsureApplicationConversation;
use App\Chat\Actions\PostSystemMessage;
use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Puts a status change inline in the conversation, so an employer manages an
 * applicant in one place instead of switching between a chat and a status
 * dropdown.
 *
 * Queued for the same reason as OpenApplicationConversation: changing a status
 * must not be able to fail because chat did.
 *
 * Ensures the conversation first — an application created before chat existed
 * has none yet, and that call is idempotent.
 */
class AnnounceApplicationStatusChange implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Application $application,
        public ApplicationStatus $status,
    ) {}

    public function handle(
        EnsureApplicationConversation $ensureApplicationConversation,
        PostSystemMessage $postSystemMessage,
    ): void {
        $conversation = $ensureApplicationConversation->handle($this->application);

        $postSystemMessage->handle(
            $conversation,
            __('Application status changed to :status.', ['status' => $this->status->value]),
        );
    }
}
