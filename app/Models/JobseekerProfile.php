<?php

namespace App\Models;

use App\Enums\Availability;
use App\Enums\Country;
use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\Gender;
use App\Enums\SalaryPeriod;
use Database\Factories\JobseekerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;

/**
 * @property string $id
 * @property string $user_id
 * @property string $full_name
 * @property string $current_title
 * @property string|null $current_company
 * @property string|null $preferred_job_title
 * @property string|null $summary
 * @property array<int, string> $skills
 * @property int $experience_years
 * @property string $country
 * @property string|null $state
 * @property string $city
 * @property string|null $preferred_country
 * @property string|null $preferred_state
 * @property string|null $preferred_city
 * @property int|null $expected_salary_min
 * @property int|null $expected_salary_max
 * @property Currency|null $expected_salary_currency
 * @property SalaryPeriod|null $expected_salary_period
 * @property Availability|null $availability
 * @property Gender|null $gender
 * @property Carbon|null $date_of_birth
 * @property string|null $phone
 * @property string|null $whatsapp
 * @property string|null $avatar_path
 * @property string|null $resume_path
 * @property Carbon|null $last_active_at
 * @property int $profile_views_count
 * @property int $resume_downloads_count
 * @property int $employer_actions_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Collection<int, Application> $applications
 * @property-read Collection<int, WorkExperience> $workExperiences
 * @property-read Collection<int, Education> $educations
 * @property-read Collection<int, JobseekerLanguage> $languages
 */
#[Fillable(['full_name', 'current_title', 'current_company', 'preferred_job_title', 'summary', 'skills', 'experience_years', 'country', 'state', 'city', 'preferred_country', 'preferred_state', 'preferred_city', 'expected_salary_min', 'expected_salary_max', 'expected_salary_currency', 'expected_salary_period', 'availability', 'gender', 'date_of_birth', 'phone', 'whatsapp', 'avatar_path', 'resume_path'])]
class JobseekerProfile extends Model
{
    /** @use HasFactory<JobseekerProfileFactory> */
    use HasFactory, HasUlids, Searchable;

    /**
     * Ranked worst-to-best so the highest attained level can be picked by
     * position. Kept here rather than on the enum because the enum is also a
     * job *requirement*, where ordering carries no meaning.
     *
     * @var list<string>
     */
    private const array EDUCATION_RANK = [
        'none',
        'high_school',
        'diploma',
        'bachelor',
        'master',
        'doctorate',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return HasMany<WorkExperience, $this>
     */
    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class)
            ->orderBy('sort')
            ->orderByDesc('start_date');
    }

    /**
     * @return HasMany<Education, $this>
     */
    public function educations(): HasMany
    {
        return $this->hasMany(Education::class)
            ->orderBy('sort')
            ->orderByDesc('end_date');
    }

    /**
     * @return HasMany<JobseekerLanguage, $this>
     */
    public function languages(): HasMany
    {
        return $this->hasMany(JobseekerLanguage::class)->orderBy('sort');
    }

    /**
     * Age in whole years, or null when no birth date is recorded. Derived
     * rather than stored so a displayed age can never go stale (ADR 0012).
     */
    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * The best level across the education rows, used as the search facet the
     * old `education_level` column used to supply.
     */
    public function highestEducationLevel(): ?EducationLevel
    {
        $best = null;

        foreach ($this->educations as $education) {
            $rank = array_search($education->level?->value, self::EDUCATION_RANK, true);

            if ($rank !== false && ($best === null || $rank > $best)) {
                $best = $rank;
            }
        }

        return $best === null ? null : EducationLevel::from(self::EDUCATION_RANK[$best]);
    }

    /**
     * Every profile is discoverable for now. A future jobseeker opt-out
     * (consent flag) plugs in here; employer-side quota gating lives in the
     * controller/policy layer, not the index.
     */
    public function shouldBeSearchable(): bool
    {
        return true;
    }

    /**
     * The text fields feeding the Typesense `embedding` (see config/scout.php)
     * must ALWAYS be present — Typesense fails to embed a document with a
     * missing source field — so they fall back to current-role data or an
     * empty string instead of being null-filtered. Only the optional facet
     * fields are filtered when null. The `embedding` field itself is
     * generated server-side and must not be sent here.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['workExperiences', 'educations', 'languages']);

        $currentLocation = $this->formatLocation($this->city, $this->state, $this->country);
        $preferredLocation = $this->formatLocation($this->preferred_city, $this->preferred_state, $this->preferred_country);

        $experienceTitles = $this->workExperiences->pluck('job_title')->all();

        return [
            'id' => (string) $this->id,
            'full_name' => $this->full_name,
            'preferred_job_title' => $this->preferred_job_title ?? $this->current_title,
            'experience_titles' => $experienceTitles === [] ? [$this->current_title] : $experienceTitles,
            'skills' => $this->skills,
            'summary' => $this->summary ?? '',
            'current_company' => $this->current_company ?? '',
            'preferred_location' => $preferredLocation ?? $currentLocation,
            'location' => $currentLocation,
            'experience_years' => $this->experience_years,
            'experience_band' => $this->experienceBand(),
            'country' => $this->country,
            'city' => $this->city,
            'education_institutions' => $this->educations->pluck('institution')->all(),
            'language_codes' => $this->languages->pluck('language.value')->all(),
            'profile_views_count' => $this->profile_views_count,
            ...array_filter([
                'state' => $this->state,
                'preferred_country' => $this->preferred_country,
                'preferred_state' => $this->preferred_state,
                'preferred_city' => $this->preferred_city,
                'availability' => $this->availability?->value,
                'gender' => $this->gender?->value,
                'education_level' => $this->highestEducationLevel()?->value,
                'expected_salary_min' => $this->expected_salary_min,
                'expected_salary_max' => $this->expected_salary_max,
                'expected_salary_currency' => $this->expected_salary_currency?->value,
                'last_active_at' => $this->last_active_at?->getTimestamp(),
            ], fn (mixed $value): bool => $value !== null),
            'created_at' => $this->created_at?->getTimestamp() ?? 0,
        ];
    }

    /**
     * Bucket experience years into the facet bands the search UI offers.
     */
    public function experienceBand(): string
    {
        return match (true) {
            $this->experience_years <= 1 => '0-1',
            $this->experience_years <= 4 => '2-4',
            $this->experience_years <= 9 => '5-9',
            default => '10+',
        };
    }

    private function formatLocation(?string $city, ?string $state, ?string $country): ?string
    {
        $parts = array_filter([$city, $state, $country === null ? null : Country::tryFrom($country)?->label()]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience_years' => 'integer',
            'expected_salary_min' => 'integer',
            'expected_salary_max' => 'integer',
            'expected_salary_currency' => Currency::class,
            'expected_salary_period' => SalaryPeriod::class,
            'availability' => Availability::class,
            'gender' => Gender::class,
            'date_of_birth' => 'date',
            'last_active_at' => 'datetime',
            'profile_views_count' => 'integer',
            'resume_downloads_count' => 'integer',
            'employer_actions_count' => 'integer',
        ];
    }
}
