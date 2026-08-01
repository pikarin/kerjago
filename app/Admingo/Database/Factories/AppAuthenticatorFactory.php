<?php

namespace App\Admingo\Database\Factories;

use App\Admingo\Models\AppAuthenticator;
use App\Admingo\Models\StaffUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppAuthenticator>
 */
class AppAuthenticatorFactory extends Factory
{
    /** @var class-string<AppAuthenticator> */
    protected $model = AppAuthenticator::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => StaffUser::factory(),
            'secret' => 'JBSWY3DPEHPK3PXP',
            'recovery_codes' => ['recovery-code-1', 'recovery-code-2'],
        ];
    }
}
