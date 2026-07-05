<?php

namespace App\Http\Requests;

use App\Enums\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchTalentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isEmployer() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', Rule::enum(Country::class)],
            'city' => ['nullable', 'string', 'max:255'],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }

    /**
     * The validated search filters.
     *
     * @return array{q?: string|null, country?: string|null, city?: string|null, experience_min?: int|null}
     */
    public function filters(): array
    {
        /** @var array{q?: string|null, country?: string|null, city?: string|null, experience_min?: int|null} */
        return $this->validated();
    }
}
