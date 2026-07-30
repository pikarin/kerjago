<?php

namespace App\Support\Chat;

use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Models\Conversation;

/**
 * Membership is the whole rule, for now.
 *
 * Notably staff do NOT get blanket access by role: internal team see a
 * conversation by being added to it, the same as anyone else. Reading every
 * conversation on the platform would be a surveillance capability, and it is
 * not required by anything currently asked for.
 *
 * This class exists as the seam. It is where a future rule goes, and routing
 * both the HTTP policy and the broadcast presence channel through it is what
 * stops those two from drifting apart — the usual way a chat app ends up
 * authorizing the page but not the socket.
 */
class PolicyChatAuthorizer implements ChatAuthorizer
{
    public function canAccess(string $participantId, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($participantId);
    }
}
