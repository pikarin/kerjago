<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    /**
     * Authorization is the ConversationPolicy's job and runs in the controller,
     * where the resolved Conversation is available.
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
            'body' => ['required', 'string', 'max:5000'],
            'parent_message_id' => ['nullable', 'ulid'],
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
