<?php

namespace App\Http\Requests;

use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SearchTalentRequest extends FormRequest
{
    /**
     * Facet params that accept multiple values.
     */
    private const array ARRAY_FILTERS = [
        'preferred_job_title',
        'skills',
        'experience_band',
        'availability',
        'country',
        'city',
        'preferred_country',
        'preferred_city',
        'languages',
        'education_level',
        'gender',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isEmployer() ?? false;
    }

    /**
     * Facet params used to be scalars (?country=ID); wrap them so old
     * bookmarked URLs keep working against the array rules.
     */
    protected function prepareForValidation(): void
    {
        $wrapped = [];

        foreach (self::ARRAY_FILTERS as $param) {
            if ($this->has($param) && ! is_array($this->input($param))) {
                $wrapped[$param] = Arr::wrap($this->input($param));
            }
        }

        if ($wrapped !== []) {
            $this->merge($wrapped);
        }
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
            'preferred_job_title' => ['nullable', 'array', 'max:10'],
            'preferred_job_title.*' => ['string', 'max:255'],
            'skills' => ['nullable', 'array', 'max:10'],
            'skills.*' => ['string', 'max:50'],
            'experience_band' => ['nullable', 'array'],
            'experience_band.*' => [Rule::in(['0-1', '2-4', '5-9', '10+'])],
            'availability' => ['nullable', 'array'],
            'availability.*' => [Rule::enum(Availability::class)],
            'country' => ['nullable', 'array'],
            'country.*' => [Rule::enum(Country::class)],
            'city' => ['nullable', 'array', 'max:10'],
            'city.*' => ['string', 'max:255'],
            'preferred_country' => ['nullable', 'array'],
            'preferred_country.*' => [Rule::enum(Country::class)],
            'preferred_city' => ['nullable', 'array', 'max:10'],
            'preferred_city.*' => ['string', 'max:255'],
            'languages' => ['nullable', 'array'],
            'languages.*' => [Rule::enum(Language::class)],
            'education_level' => ['nullable', 'array'],
            'education_level.*' => [Rule::enum(EducationLevel::class)],
            'gender' => ['nullable', 'array'],
            'gender.*' => [Rule::enum(Gender::class)],
            'experience_min' => ['nullable', 'integer', 'min:0', 'max:60'],
        ];
    }

    /**
     * The validated search filters.
     *
     * @return array{q?: string|null, preferred_job_title?: list<string>|null, skills?: list<string>|null, experience_band?: list<string>|null, availability?: list<string>|null, country?: list<string>|null, city?: list<string>|null, preferred_country?: list<string>|null, preferred_city?: list<string>|null, languages?: list<string>|null, education_level?: list<string>|null, gender?: list<string>|null, experience_min?: int|null}
     */
    public function filters(): array
    {
        /** @var array{q?: string|null, preferred_job_title?: list<string>|null, skills?: list<string>|null, experience_band?: list<string>|null, availability?: list<string>|null, country?: list<string>|null, city?: list<string>|null, preferred_country?: list<string>|null, preferred_city?: list<string>|null, languages?: list<string>|null, education_level?: list<string>|null, gender?: list<string>|null, experience_min?: int|null} */
        return $this->validated();
    }
}
