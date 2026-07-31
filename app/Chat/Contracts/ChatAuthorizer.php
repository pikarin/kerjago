<?php

namespace App\Chat\Contracts;

use App\Chat\Models\Conversation;

/**
 * Implemented by the host application.
 *
 * Membership is the baseline rule and the module can answer that itself, but
 * who else may look — internal staff, for instance — is a host policy
 * question. Both the HTTP routes and the broadcast presence channel funnel
 * through this one method so they cannot drift apart.
 */
interface ChatAuthorizer
{
    public function canAccess(string $participantId, Conversation $conversation): bool;
}
