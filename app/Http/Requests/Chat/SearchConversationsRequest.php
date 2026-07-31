<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mirrors SearchJobsRequest and SearchTalentRequest: the inbox was the only
 * search endpoint handing an unbounded, untyped string straight to the search
 * engine.
 */
class SearchConversationsRequest extends FormRequest
{
    /**
     * The inbox is scoped to the authenticated participant inside
     * ListConversations and SearchMessages; there is no model to authorize.
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
            'q' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function searchQuery(): string
    {
        return $this->string('q')->trim()->toString();
    }
}
