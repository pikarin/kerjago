<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isJobseeker() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'cover_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get the validated cover note.
     */
    public function coverNote(): ?string
    {
        $coverNote = $this->validated('cover_note');

        return is_string($coverNote) ? $coverNote : null;
    }
}
