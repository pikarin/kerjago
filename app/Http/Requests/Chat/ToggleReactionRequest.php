<?php

namespace App\Http\Requests\Chat;

use App\Chat\Models\Message;
use Illuminate\Validation\Rule;

class ToggleReactionRequest extends ChatConversationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Scoped to this conversation, so a genuine message id belonging to
            // a conversation the caller cannot see is rejected rather than
            // reacted to. Soft-deleted rows are excluded, otherwise a deleted
            // message passes validation and then 404s in the controller.
            'message_id' => [
                'required',
                Rule::exists(Message::class, 'id')
                    ->where('conversation_id', $this->conversationId())
                    ->whereNull('deleted_at'),
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
}
