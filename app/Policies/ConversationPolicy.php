<?php

namespace App\Policies;

use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Models\Conversation;
use App\Models\User;

/**
 * Delegates to ChatAuthorizer, the same contract routes/channels.php uses for
 * the presence channel. Both funnel through one method so the page and the
 * socket cannot end up with different answers.
 *
 * Registered explicitly in AppServiceProvider: policy auto-discovery looks for
 * App\Policies\{Model}Policy against App\Models, and Conversation lives in
 * App\Chat\Models.
 */
class ConversationPolicy
{
    public function __construct(private ChatAuthorizer $authorizer) {}

    public function view(User $user, Conversation $conversation): bool
    {
        return $this->authorizer->canAccess($user->id, $conversation);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $this->authorizer->canAccess($user->id, $conversation);
    }
}
