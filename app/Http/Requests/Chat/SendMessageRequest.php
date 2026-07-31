<?php

namespace App\Http\Requests\Chat;

use App\Chat\Models\Message;
use Illuminate\Validation\Rule;

class SendMessageRequest extends ChatConversationRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],

            // Scoped to this conversation, and excluding soft-deleted rows.
            // parent_message_id carries a real self-referential foreign key, so
            // a merely well-formed ULID would pass a `ulid` rule and then fail
            // the constraint as a 500 instead of a validation error. Scoping
            // also stops a valid id from another conversation being stored as a
            // thread parent that crosses a conversation boundary.
            'parent_message_id' => [
                'nullable',
                Rule::exists(Message::class, 'id')
                    ->where('conversation_id', $this->conversationId())
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    public function body(): string
    {
        return (string) $this->string('body');
    }

    public function parentMessageId(): ?string
    {
        $parent = $this->input('parent_message_id');

        return is_string($parent) && $parent !== '' ? $parent : null;
    }
}
