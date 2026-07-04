<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\JobStatus;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

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
 * @property JobStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EmployerProfile $employerProfile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Application> $applications
 */
#[Fillable(['title', 'description', 'skills', 'location_country', 'location_city', 'salary_min', 'salary_max', 'currency', 'status'])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory, HasUlids;

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
            'status' => JobStatus::class,
        ];
    }
}
