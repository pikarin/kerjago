<?php

namespace App\Models;

use App\Enums\UnlockSource;
use Database\Factories\CandidateUnlockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One employer's permission to see one jobseeker's full identity until
 * `expires_at`. Keyed to the pair, not to the application or the job, so an
 * unlock earned through one job opens that candidate everywhere the employer
 * meets them.
 *
 * @property string $id
 * @property string $employer_profile_id
 * @property string $jobseeker_profile_id
 * @property string|null $job_id
 * @property UnlockSource $source
 * @property Carbon $expires_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EmployerProfile $employerProfile
 * @property-read JobseekerProfile $jobseekerProfile
 * @property-read Job|null $job
 */
#[Fillable(['employer_profile_id', 'jobseeker_profile_id', 'job_id', 'source', 'expires_at'])]
class CandidateUnlock extends Model
{
    /** @use HasFactory<CandidateUnlockFactory> */
    use HasFactory, HasUlids;

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    /**
     * @return BelongsTo<JobseekerProfile, $this>
     */
    public function jobseekerProfile(): BelongsTo
    {
        return $this->belongsTo(JobseekerProfile::class);
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * Unlocks still in force. An expired row is kept rather than deleted so a
     * candidate is never unlocked twice by the same job's quota.
     *
     * @param  Builder<CandidateUnlock>  $query
     * @return Builder<CandidateUnlock>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source' => UnlockSource::class,
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
