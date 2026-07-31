<?php

namespace App\Http\Controllers\Chat;

use App\Chat\Actions\SendMessage;
use App\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function store(
        SendMessageRequest $request,
        Conversation $conversation,
        SendMessage $sendMessage,
    ): RedirectResponse {
        Gate::authorize('sendMessage', $conversation);

        /** @var User $user */
        $user = $request->user();

        $conversation->loadMissing('participants');

        $sendMessage->handle(
            $conversation,
            $user->id,
            $request->body(),
            $request->parentMessageId(),
        );

        return back();
    }
}
