<?php

namespace App\Models;

use Database\Factories\EmployerProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property string $company_name
 * @property string $industry
 * @property string $country
 * @property string $city
 * @property string|null $website
 * @property Carbon|null $verified_at
 * @property string|null $verified_by_id
 * @property Carbon|null $verification_requested_at
 * @property string|null $publish_batch_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read User|null $verifiedBy
 * @property-read Collection<int, Job> $jobs
 * @property-read Collection<int, EmployerVerificationEvent> $verificationEvents
 */
#[Fillable(['company_name', 'industry', 'country', 'city', 'website'])]
class EmployerProfile extends Model
{
    /** @use HasFactory<EmployerProfileFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The staff member whose decision put the company in its current state.
     * Overwritten by each decision; the history lives in verificationEvents.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_id');
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * @return HasMany<EmployerVerificationEvent, $this>
     */
    public function verificationEvents(): HasMany
    {
        return $this->hasMany(EmployerVerificationEvent::class);
    }

    /**
     * Whether the company has cleared the verification precondition.
     *
     * Read this only from `EmployerCapabilities` and from the surfaces that are
     * genuinely *about* verification — the Admingo queue, the employer's own
     * status banner. Anywhere that is really asking "may they do X?" must ask
     * the resolver instead, or the next gate added alongside verification will
     * need every call site found again.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Companies still waiting on a decision, whether or not they asked.
     *
     * @param  Builder<EmployerProfile>  $query
     * @return Builder<EmployerProfile>
     */
    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereNull('verified_at');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'verification_requested_at' => 'datetime',
        ];
    }
}
