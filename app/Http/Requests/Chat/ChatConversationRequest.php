<?php

namespace App\Http\Requests\Chat;

use App\Chat\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base for requests bound to a {conversation} route parameter.
 *
 * Authorization lives here rather than in the controller because FormRequest
 * rules run *before* the controller body. Several of those rules probe the
 * database scoped to this conversation, so authorizing later would let a
 * non-participant distinguish "no such message here" (422) from "that message
 * exists here" (403) — a membership oracle. It also matches the project's
 * existing convention: UpdateApplicationStatusRequest authorizes off
 * $this->route(...) the same way.
 */
abstract class ChatConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('sendMessage', $this->route('conversation')) ?? false;
    }

    /**
     * Implicit binding resolves this to a Conversation. The string branch covers
     * a raw id, and the empty fallback makes a scoped `exists` rule match
     * nothing rather than accidentally matching every conversation.
     */
    protected function conversationId(): string
    {
        $conversation = $this->route('conversation');

        if ($conversation instanceof Conversation) {
            return $conversation->id;
        }

        return is_string($conversation) ? $conversation : '';
    }
}
