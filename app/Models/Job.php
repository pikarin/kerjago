<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\EducationLevel;
use App\Enums\EmploymentType;
use App\Enums\ExperienceLevel;
use App\Enums\JobStatus;
use App\Enums\WorkArrangement;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string $employer_profile_id
 * @property string $title
 * @property string $description
 * @property array<int, string> $skills
 * @property string $location_country
 * @property string $location_city
 * @property int $salary_min
 * @property int $salary_max
 * @property Currency $currency
 * @property EmploymentType|null $employment_type
 * @property WorkArrangement|null $work_arrangement
 * @property ExperienceLevel|null $experience_level
 * @property EducationLevel|null $education_level
 * @property JobStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EmployerProfile $employerProfile
 * @property-read Collection<int, Application> $applications
 */
#[Fillable(['title', 'description', 'skills', 'location_country', 'location_city', 'salary_min', 'salary_max', 'currency', 'employment_type', 'work_arrangement', 'experience_level', 'education_level', 'status'])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory, HasUlids, Searchable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @param  Builder<Job>  $query
     * @return Builder<Job>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Active);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === JobStatus::Active;
    }

    /**
     * Nulls are filtered out so Typesense `optional` facet fields are simply
     * absent on legacy documents; the `embedding` field is generated
     * server-side by Typesense and must not be sent here.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return array_filter([
            'id' => (string) $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'skills' => $this->skills,
            'location_country' => $this->location_country,
            'location_city' => $this->location_city,
            'employment_type' => $this->employment_type?->value,
            'work_arrangement' => $this->work_arrangement?->value,
            'experience_level' => $this->experience_level?->value,
            'education_level' => $this->education_level?->value,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency->value,
            'created_at' => $this->created_at?->getTimestamp() ?? 0,
        ], fn (mixed $value): bool => $value !== null);
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
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'currency' => Currency::class,
            'employment_type' => EmploymentType::class,
            'work_arrangement' => WorkArrangement::class,
            'experience_level' => ExperienceLevel::class,
            'education_level' => EducationLevel::class,
            'status' => JobStatus::class,
        ];
    }
}
