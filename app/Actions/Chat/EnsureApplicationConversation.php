<?php

namespace App\Actions\Chat;

use App\Actions\Unlocks\ResolveUnlockedProfileIds;
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
    public function __construct(
        private OpenConversation $openConversation,
        private ResolveUnlockedProfileIds $resolveUnlockedProfileIds,
    ) {}

    /**
     * Idempotent: the unique key means applying twice, or a retried queue job,
     * converges on one conversation rather than creating another.
     *
     * The thread is opened for every applicant, locked or not — it is how the
     * jobseeker receives status system messages. What the lock withholds is the
     * *employer's* access: without an unlock they join with `left_at` already
     * set, so the thread is absent from their inbox and closed to them until
     * IssueCandidateUnlock restores it (ADR 0013).
     */
    public function handle(Application $application): Conversation
    {
        $application->loadMissing(['jobseekerProfile', 'job.employerProfile']);

        $jobseekerUserId = $application->jobseekerProfile->user_id;
        $employerProfile = $application->job->employerProfile;
        $employerUserId = $employerProfile->user_id;

        $employerIsUnlocked = $this->resolveUnlockedProfileIds->has(
            $employerProfile,
            $application->jobseeker_profile_id,
        );

        return $this->openConversation->handle(new NewConversation(
            kind: ConversationKind::Application->value,
            createdByParticipantId: $jobseekerUserId,
            participantIds: $employerIsUnlocked
                ? [$jobseekerUserId, $employerUserId]
                : [$jobseekerUserId],
            withheldParticipantIds: $employerIsUnlocked ? [] : [$employerUserId],
            contextType: ChatContextType::Application->value,
            contextId: $application->id,
            uniqueKey: ChatContextType::Application->value.':'.$application->id,
        ));
    }
}
