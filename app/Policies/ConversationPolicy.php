<?php

namespace App\Policies;

use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Models\Conversation;
use App\Enums\ConversationKind;
use App\Enums\EmployerCapability;
use App\Models\User;
use App\Support\Capabilities\EmployerCapabilities;

/**
 * Delegates to ChatAuthorizer, the same contract routes/channels.php uses for
 * the presence channel. Both funnel through one method so the page and the
 * socket cannot end up with different answers.
 */
class ConversationPolicy
{
    public function __construct(
        private ChatAuthorizer $authorizer,
        private EmployerCapabilities $capabilities,
    ) {}

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->authorizer->canAccess($user->id, $conversation);
    }

    /**
     * The single write gate for chat — messages and reactions both authorize
     * through it.
     *
     * An employer without `ParticipateInChat` keeps `view`, so their threads
     * stay readable and the candidate's messages still arrive somewhere a
     * person can see them. Only writing stops. Withholding the threads outright
     * is the mechanism unlock expiry uses, where the lock is a fact about the
     * candidate's PII; a company losing its standing is not a reason to leave
     * the jobseeker talking into an empty room.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (! $this->authorizer->canAccess($user->id, $conversation)) {
            return false;
        }

        $employerProfile = $user->employerProfile;

        // Jobseekers and staff hold no employer profile and are never gated
        // here.
        if ($employerProfile === null) {
            return true;
        }

        // Never gate the line to support. Closing it would leave a company
        // unable to reach the only people who can restore its standing —
        // through the one channel we control.
        if ($conversation->kind === ConversationKind::Internal->value) {
            return true;
        }

        return $this->capabilities->allows($employerProfile, EmployerCapability::ParticipateInChat);
    }
}
