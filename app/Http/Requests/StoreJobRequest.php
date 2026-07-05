<?php

namespace App\Http\Requests;

use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkArrangement;
use App\Models\Job;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Job::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'skills' => ['required', 'array', 'min:1', 'max:20'],
            'skills.*' => ['required', 'string', 'max:50'],
            'location_country' => ['required', Rule::enum(Country::class)],
            'location_city' => ['required', 'string', 'max:255'],
            'salary_min' => ['required', 'integer', 'min:0'],
            'salary_max' => ['required', 'integer', 'gte:salary_min'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'work_arrangement' => ['required', Rule::enum(WorkArrangement::class)],
            'experience_level' => ['required', Rule::enum(ExperienceLevel::class)],
            'education_level' => ['required', Rule::enum(EducationLevel::class)],
            'status' => ['required', Rule::enum(JobStatus::class)],
        ];
    }
}
