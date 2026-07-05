<?php

namespace App\Http\Requests;

use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobSort;
use App\Enums\WorkArrangement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchJobsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'country' => ['nullable', 'array'],
            'country.*' => [Rule::enum(Country::class)],
            'employment_type' => ['nullable', 'array'],
            'employment_type.*' => [Rule::enum(EmploymentType::class)],
            'work_arrangement' => ['nullable', 'array'],
            'work_arrangement.*' => [Rule::enum(WorkArrangement::class)],
            'experience_level' => ['nullable', 'array'],
            'experience_level.*' => [Rule::enum(ExperienceLevel::class)],
            'education_level' => ['nullable', 'array'],
            'education_level.*' => [Rule::enum(EducationLevel::class)],
            'skills' => ['nullable', 'array', 'max:10'],
            'skills.*' => ['string', 'max:50'],
            'currency' => ['nullable', Rule::enum(Currency::class)],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'min:0'],
            'sort' => ['nullable', Rule::enum(JobSort::class)],
        ];
    }

    /**
     * The validated search filters.
     *
     * @return array{q?: string|null, country?: list<string>|null, employment_type?: list<string>|null, work_arrangement?: list<string>|null, experience_level?: list<string>|null, education_level?: list<string>|null, skills?: list<string>|null, currency?: string|null, salary_min?: int|null, salary_max?: int|null, sort?: string|null}
     */
    public function filters(): array
    {
        /** @var array{q?: string|null, country?: list<string>|null, employment_type?: list<string>|null, work_arrangement?: list<string>|null, experience_level?: list<string>|null, education_level?: list<string>|null, skills?: list<string>|null, currency?: string|null, salary_min?: int|null, salary_max?: int|null, sort?: string|null} */
        return $this->validated();
    }
}
