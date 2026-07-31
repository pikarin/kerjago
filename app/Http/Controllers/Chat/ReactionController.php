<?php

namespace App\Http\Controllers\Chat;

use App\Chat\Actions\ToggleReaction;
use App\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ToggleReactionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ReactionController extends Controller
{
    /**
     * Authorization runs in ToggleReactionRequest.
     */
    public function store(
        ToggleReactionRequest $request,
        Conversation $conversation,
        ToggleReaction $toggleReaction,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        // Resolved through the conversation's own messages, so the controller
        // stays correct even if the request's scoping rule is later loosened.
        $message = $conversation->messages()->findOrFail($request->messageId());

        $toggleReaction->handle($message, $user->id, $request->emoji());

        return back();
    }
}
