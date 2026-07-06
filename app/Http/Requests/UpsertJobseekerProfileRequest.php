<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
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
     * Experience row ids are validated as opaque strings only; ownership is
     * enforced in UpsertJobseekerProfile by matching ids within the
     * profile's own rows.
     *
     * @return array<string, array<int, ValidationRule|File|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'current_title' => ['required', 'string', 'max:255'],
            'preferred_job_title' => ['nullable', 'string', 'max:255'],
            'skills' => ['required', 'array', 'min:1', 'max:30'],
            'skills.*' => ['required', 'string', 'max:50'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'country' => ['required', Rule::enum(Country::class)],
            'city' => ['required', 'string', 'max:255'],
            'preferred_country' => ['nullable', Rule::enum(Country::class)],
            'preferred_city' => ['nullable', 'string', 'max:255'],
            'availability' => ['nullable', Rule::enum(Availability::class)],
            'languages' => ['nullable', 'array'],
            'languages.*' => [Rule::enum(Language::class)],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            'education_level' => ['nullable', Rule::enum(EducationLevel::class)],
            'phone' => ['nullable', 'string', 'phone:ID,SG,MY,PH,VN,TH,INTERNATIONAL'],
            'resume' => [
                'nullable',
                File::types(['pdf', 'doc', 'docx'])->max(5 * 1024),
            ],
            'experiences' => ['nullable', 'array', 'max:20'],
            'experiences.*.id' => ['nullable', 'string', 'max:26'],
            'experiences.*.job_title' => ['required', 'string', 'max:255'],
            'experiences.*.company_name' => ['required', 'string', 'max:255'],
            'experiences.*.start_date' => ['required', 'date', 'before_or_equal:today'],
            'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
        ];
    }
}
