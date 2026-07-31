<?php

namespace App\Http\Requests\Chat;

use App\Chat\Models\Conversation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleReactionRequest extends FormRequest
{
    /**
     * Access to the conversation is checked by ConversationPolicy in the
     * controller, where the resolved model is available.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Scoped to this conversation, so a genuine message id belonging to
            // a conversation the caller cannot see is rejected rather than
            // reacted to.
            'message_id' => [
                'required',
                Rule::exists('chat_messages', 'id')
                    ->where('conversation_id', $this->conversationId()),
            ],
            'emoji' => ['required', 'string', 'max:16'],
        ];
    }

    public function messageId(): string
    {
        return (string) $this->string('message_id');
    }

    public function emoji(): string
    {
        return (string) $this->string('emoji');
    }

    /**
     * Route model binding resolves this to a Conversation. The string branch
     * covers a raw id, and the empty fallback makes the exists rule match
     * nothing rather than accidentally matching every conversation.
     */
    private function conversationId(): string
    {
        $conversation = $this->route('conversation');

        if ($conversation instanceof Conversation) {
            return $conversation->id;
        }

        return is_string($conversation) ? $conversation : '';
    }
}
