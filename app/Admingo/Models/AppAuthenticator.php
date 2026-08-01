<?php

namespace App\Admingo\Models;

use App\Admingo\Database\Factories\AppAuthenticatorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A staff member's Filament app-authentication (TOTP) enrolment.
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $secret
 * @property array<string>|null $recovery_codes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read StaffUser $staffUser
 */
#[Fillable(['secret', 'recovery_codes'])]
#[Hidden(['secret', 'recovery_codes'])]
class AppAuthenticator extends Model
{
    /** @use HasFactory<AppAuthenticatorFactory> */
    use HasFactory, HasUlids;

    protected $table = 'admingo_app_authenticators';

    /**
     * @return BelongsTo<StaffUser, $this>
     */
    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(StaffUser::class, 'user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
        ];
    }

    /**
     * @return AppAuthenticatorFactory
     */
    protected static function newFactory(): Factory
    {
        return AppAuthenticatorFactory::new();
    }
}
