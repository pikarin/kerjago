<?php

namespace App\Actions\Profiles;

use App\Actions\Verification\UnverifyEmployer;
use App\Enums\VerificationSource;
use App\Models\EmployerProfile;
use App\Models\User;

class UpsertEmployerProfile
{
    /**
     * The fields that say *which company this is*. Changing any of them on a
     * verified profile means the thing staff checked is no longer the thing on
     * the page, so the verification does not carry over.
     *
     * Industry and city are absent deliberately: they describe the company
     * without identifying it, and a company that moves office should not lose
     * its ads.
     *
     * @var list<string>
     */
    private const array IDENTITY_FIELDS = ['company_name', 'website'];

    public function __construct(private UnverifyEmployer $unverifyEmployer) {}

    /**
     * Create or update the employer's company profile.
     *
     * A verified company that rewrites its identity is put back in the queue
     * before the change is saved. Without that, the cheapest attack on the
     * whole gate is to get a throwaway company verified and then rename it to
     * a well-known employer: the badge, Talent Search and chat would all carry
     * over to an identity nobody reviewed, and the history would record
     * nothing.
     *
     * @param  array{company_name: string, industry: string, country: string, city: string, website: string|null}  $data
     */
    public function handle(User $user, array $data): EmployerProfile
    {
        $profile = $user->employerProfile ?? new EmployerProfile;

        $profile->fill($data);

        if ($profile->exists && $profile->isVerified() && $this->identityChanged($profile)) {
            // Revoked first, so the ads come down against the identity that
            // was actually verified. The Action re-reads under a row lock, so
            // the unsaved changes here do not reach it.
            $this->unverifyEmployer->handle(
                $profile->fresh() ?? $profile,
                reason: 'Company identity changed after verification: '.$this->changedIdentityFields($profile),
                source: VerificationSource::System,
                employerMessage: __('Your company details changed, so we need to check them again before your jobs go back up.'),
            );

            // Queued for review in the same breath. The employer is told a
            // re-check is coming, and nobody asked them to ask — so without
            // this the company would sit outside the Admingo badge and sort to
            // the bottom of the queue on a null timestamp, waiting on a request
            // it has no reason to make.
            $profile->forceFill([
                'verified_at' => null,
                'verified_by_id' => null,
                'verification_requested_at' => now(),
            ]);
        }

        $profile->user()->associate($user);
        $profile->save();

        return $profile;
    }

    /**
     * Whether any identifying field differs from what is stored.
     */
    private function identityChanged(EmployerProfile $profile): bool
    {
        return $profile->isDirty(self::IDENTITY_FIELDS);
    }

    /**
     * The changed field names, for the audit row. Values are left out: the
     * previous ones are still readable on the event's neighbours, and the new
     * ones are on the profile itself.
     */
    private function changedIdentityFields(EmployerProfile $profile): string
    {
        return implode(', ', array_intersect(
            self::IDENTITY_FIELDS,
            array_keys($profile->getDirty()),
        ));
    }
}
