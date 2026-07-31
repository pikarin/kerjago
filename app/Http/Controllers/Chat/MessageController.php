<?php

namespace App\Http\Controllers\Chat;

use App\Chat\Actions\SendMessage;
use App\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MessageController extends Controller
{
    /**
     * Authorization runs in SendMessageRequest, before its scoped validation
     * rules touch the database.
     */
    public function store(
        SendMessageRequest $request,
        Conversation $conversation,
        SendMessage $sendMessage,
    ): RedirectResponse {
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
