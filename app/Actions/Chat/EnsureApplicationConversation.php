<?php

namespace App\Actions\Chat;

use App\Chat\Actions\OpenConversation;
use App\Chat\Data\NewConversation;
use App\Chat\Models\Conversation;
use App\Enums\ChatContextType;
use App\Enums\ConversationKind;
use App\Models\Application;

/**
 * Host-side orchestration. This class knows what an Application is; the chat
 * module does not, which is why this lives in app/Actions/Chat rather than
 * inside app/Chat (ADR 0009).
 */
class EnsureApplicationConversation
{
    public function __construct(private OpenConversation $openConversation) {}

    /**
     * Idempotent: the unique key means applying twice, or a retried queue job,
     * converges on one conversation rather than creating another.
     */
    public function handle(Application $application): Conversation
    {
        $application->loadMissing(['jobseekerProfile', 'job.employerProfile']);

        $jobseekerUserId = $application->jobseekerProfile->user_id;
        $employerUserId = $application->job->employerProfile->user_id;

        return $this->openConversation->handle(new NewConversation(
            kind: ConversationKind::Application->value,
            createdByParticipantId: $jobseekerUserId,
            participantIds: [$jobseekerUserId, $employerUserId],
            contextType: ChatContextType::Application->value,
            contextId: $application->id,
            uniqueKey: ChatContextType::Application->value.':'.$application->id,
        ));
    }
}
