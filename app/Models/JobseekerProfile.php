<?php

namespace App\Models;

use Database\Factories\JobseekerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $full_name
 * @property string $current_title
 * @property array<int, string> $skills
 * @property int $experience_years
 * @property string $country
 * @property string $city
 * @property string|null $phone
 * @property string|null $resume_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Application> $applications
 */
#[Fillable(['full_name', 'current_title', 'skills', 'experience_years', 'country', 'city', 'phone', 'resume_path'])]
class JobseekerProfile extends Model
{
    /** @use HasFactory<JobseekerProfileFactory> */
    use HasFactory, HasUlids;

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'experience_years' => 'integer',
        ];
    }
}
