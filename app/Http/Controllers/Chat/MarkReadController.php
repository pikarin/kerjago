<?php

namespace App\Http\Controllers\Chat;

use App\Chat\Actions\MarkConversationRead;
use App\Chat\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MarkReadController extends Controller
{
    /**
     * Explicit read acknowledgement, for a client that keeps a conversation
     * open while new messages arrive over the socket.
     */
    public function store(
        Request $request,
        Conversation $conversation,
        MarkConversationRead $markConversationRead,
    ): RedirectResponse {
        Gate::authorize('view', $conversation);

        /** @var User $user */
        $user = $request->user();

        $markConversationRead->handle($conversation, $user->id);

        return back();
    }
}
