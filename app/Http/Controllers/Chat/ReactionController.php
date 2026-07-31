<?php

namespace App\Http\Controllers\Chat;

use App\Chat\Actions\ToggleReaction;
use App\Chat\Models\Conversation;
use App\Chat\Models\Message;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ToggleReactionRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReactionController extends Controller
{
    public function store(
        ToggleReactionRequest $request,
        Conversation $conversation,
        ToggleReaction $toggleReaction,
    ): RedirectResponse {
        Gate::authorize('sendMessage', $conversation);

        /** @var User $user */
        $user = $request->user();

        $message = Message::query()->findOrFail($request->messageId());

        $toggleReaction->handle($message, $user->id, $request->emoji());

        return back();
    }
}
