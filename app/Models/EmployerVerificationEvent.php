<?php

namespace App\Models;

use App\Enums\VerificationDecision;
use App\Enums\VerificationSource;
use Database\Factories\EmployerVerificationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One decision in an Employer Profile's verification history.
 *
 * Append-only: rows are written and never touched again, which is why there is
 * no `updated_at`. Nothing reads this table to make a decision — current state
 * lives on the profile — so it can grow columns later without the capability
 * resolver noticing.
 *
 * `reason` is internal and `employer_message` is the only part quoted back to
 * the company. Two fields on purpose: with one, staff either self-censor their
 * audit notes or mail fraud suspicions to the suspected fraudster.
 *
 * @property string $id
 * @property string $employer_profile_id
 * @property VerificationDecision $decision
 * @property VerificationSource $source
 * @property string|null $actor_id
 * @property string|null $reason
 * @property string|null $employer_message
 * @property Carbon|null $created_at
 * @property-read EmployerProfile $employerProfile
 * @property-read User|null $actor
 */
#[Fillable(['employer_profile_id', 'decision', 'source', 'actor_id', 'reason', 'employer_message'])]
class EmployerVerificationEvent extends Model
{
    /** @use HasFactory<EmployerVerificationEventFactory> */
    use HasFactory, HasUlids;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<EmployerProfile, $this>
     */
    public function employerProfile(): BelongsTo
    {
        return $this->belongsTo(EmployerProfile::class);
    }

    /**
     * The staff member who decided, or null for anything the platform decided
     * on its own.
     *
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decision' => VerificationDecision::class,
            'source' => VerificationSource::class,
            'created_at' => 'datetime',
        ];
    }
}
