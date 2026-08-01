<?php

namespace App\Admingo\Models;

use App\Admingo\Database\Factories\StaffUserFactory;
use App\Enums\UserRole;
use App\Models\User;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use SensitiveParameter;

/**
 * The Admingo guard's projection of a staff `users` row.
 *
 * Two Eloquent models share the `users` table on purpose. This one exists so
 * that every Filament contract — panel access and multi-factor credentials —
 * lands inside App\Admingo, leaving App\Models\User with no knowledge that
 * Filament exists. tests/Unit/Admingo/ArchitectureTest.php enforces that, with
 * no allow-list. See docs/adr/0011.
 *
 * The global scope is also the outermost of the panel's three access gates: a
 * non-staff row is not merely denied, it is unretrievable by the guard, so no
 * session and no Livewire request can ever be established for one.
 *
 * @property-read AppAuthenticator|null $appAuthenticator
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class StaffUser extends User implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery
{
    /**
     * Shared with App\Models\User — the class name would otherwise resolve to
     * a `staff_users` table that does not exist.
     */
    protected $table = 'users';

    protected static function booted(): void
    {
        static::addGlobalScope(
            'staff',
            fn (Builder $query) => $query->where('role', UserRole::Staff->value),
        );

        // Role is deliberately not fillable: it is forced here so that
        // `make:filament-user` provisions a valid staff row against a
        // not-nullable `users.role` column, and so that nothing routed
        // through this model can create an employer or a jobseeker.
        static::creating(function (self $staffUser): void {
            $staffUser->role = UserRole::Staff;
            $staffUser->email_verified_at ??= Carbon::now();
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isStaff();
    }

    /**
     * @return HasOne<AppAuthenticator, $this>
     */
    public function appAuthenticator(): HasOne
    {
        return $this->hasOne(AppAuthenticator::class, 'user_id');
    }

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->appAuthenticator?->secret;
    }

    public function saveAppAuthenticationSecret(#[SensitiveParameter] ?string $secret): void
    {
        $this->persistAppAuthenticator(function (AppAuthenticator $authenticator) use ($secret): void {
            $authenticator->secret = $secret;
        });
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    /**
     * @return ?array<string>
     */
    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->appAuthenticator?->recovery_codes;
    }

    /**
     * @param  ?array<string>  $codes
     */
    public function saveAppAuthenticationRecoveryCodes(#[SensitiveParameter] ?array $codes): void
    {
        $this->persistAppAuthenticator(function (AppAuthenticator $authenticator) use ($codes): void {
            $authenticator->recovery_codes = $codes;
        });
    }

    /**
     * @return StaffUserFactory
     */
    protected static function newFactory(): Factory
    {
        return StaffUserFactory::new();
    }

    /**
     * Filament writes the secret and the recovery codes through separate calls,
     * so both go through here: the row is created on first write and removed
     * once nothing is left on it, which is how disenrolment arrives (Filament
     * nulls each field in turn rather than deleting anything itself).
     *
     * @param  callable(AppAuthenticator): void  $mutate
     */
    private function persistAppAuthenticator(callable $mutate): void
    {
        $authenticator = $this->appAuthenticator ?? new AppAuthenticator;

        $mutate($authenticator);

        if ($authenticator->secret === null && $authenticator->recovery_codes === null) {
            if ($authenticator->exists) {
                $authenticator->delete();
            }

            $this->unsetRelation('appAuthenticator');

            return;
        }

        $this->appAuthenticator()->save($authenticator);
        $this->setRelation('appAuthenticator', $authenticator);
    }
}
