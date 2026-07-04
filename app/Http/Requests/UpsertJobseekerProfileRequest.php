<?php

namespace App\Http\Requests;

use App\Enums\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpsertJobseekerProfileRequest extends FormRequest
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
     * @return array<string, array<int, ValidationRule|File|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'current_title' => ['required', 'string', 'max:255'],
            'skills' => ['required', 'array', 'min:1', 'max:30'],
            'skills.*' => ['required', 'string', 'max:50'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'country' => ['required', Rule::enum(Country::class)],
            'city' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'phone:ID,SG,MY,PH,VN,TH,INTERNATIONAL'],
            'resume' => [
                'nullable',
                File::types(['pdf', 'doc', 'docx'])->max(5 * 1024),
            ],
        ];
    }
}
