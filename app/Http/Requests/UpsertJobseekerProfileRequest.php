<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use App\Enums\LanguageProficiency;
use App\Enums\SalaryPeriod;
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
            'current_company' => ['nullable', 'string', 'max:255'],
            'preferred_job_title' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'skills' => ['required', 'array', 'min:1', 'max:30'],
            'skills.*' => ['required', 'string', 'max:50'],
            'experience_years' => ['required', 'integer', 'min:0', 'max:60'],
            'country' => ['required', Rule::enum(Country::class)],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'preferred_country' => ['nullable', Rule::enum(Country::class)],
            'preferred_state' => ['nullable', 'string', 'max:255'],
            'preferred_city' => ['nullable', 'string', 'max:255'],
            'expected_salary_min' => ['nullable', 'integer', 'min:0'],
            'expected_salary_max' => ['nullable', 'integer', 'min:0', 'gte:expected_salary_min'],
            'expected_salary_currency' => ['nullable', 'required_with:expected_salary_min,expected_salary_max', Rule::enum(Currency::class)],
            'expected_salary_period' => ['nullable', Rule::enum(SalaryPeriod::class)],
            'availability' => ['nullable', Rule::enum(Availability::class)],
            'gender' => ['nullable', Rule::enum(Gender::class)],
            // 15 is the statutory floor for lawful employment across the SEA
            // markets Kerjago serves; 100 is a sanity bound on typos.
            'date_of_birth' => ['nullable', 'date', 'before:-15 years', 'after:-100 years'],
            'phone' => ['nullable', 'string', 'phone:ID,SG,MY,PH,VN,TH,INTERNATIONAL'],
            'whatsapp' => ['nullable', 'string', 'phone:ID,SG,MY,PH,VN,TH,INTERNATIONAL'],
            'avatar' => [
                'nullable',
                File::image()->max(2 * 1024),
            ],
            'resume' => [
                'nullable',
                File::types(['pdf', 'doc', 'docx'])->max(5 * 1024),
            ],
            'experiences' => ['nullable', 'array', 'max:20'],
            'experiences.*.id' => ['nullable', 'string', 'max:26'],
            'experiences.*.job_title' => ['required', 'string', 'max:255'],
            'experiences.*.company_name' => ['required', 'string', 'max:255'],
            'experiences.*.description' => ['nullable', 'string', 'max:2000'],
            'experiences.*.start_date' => ['required', 'date', 'before_or_equal:today'],
            'experiences.*.end_date' => ['nullable', 'date', 'after_or_equal:experiences.*.start_date'],
            'experiences.*.is_current' => ['nullable', 'boolean'],
            'educations' => ['nullable', 'array', 'max:10'],
            'educations.*.id' => ['nullable', 'string', 'max:26'],
            'educations.*.institution' => ['required', 'string', 'max:255'],
            'educations.*.field_of_study' => ['nullable', 'string', 'max:255'],
            'educations.*.level' => ['nullable', Rule::enum(EducationLevel::class)],
            'educations.*.start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'educations.*.end_date' => ['nullable', 'date', 'after_or_equal:educations.*.start_date'],
            'languages' => ['nullable', 'array', 'max:10'],
            'languages.*.id' => ['nullable', 'string', 'max:26'],
            'languages.*.language' => ['required', Rule::enum(Language::class), 'distinct'],
            'languages.*.proficiency' => ['required', Rule::enum(LanguageProficiency::class)],
        ];
    }
}
