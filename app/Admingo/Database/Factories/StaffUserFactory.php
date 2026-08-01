<?php

namespace App\Admingo\Database\Factories;

use App\Admingo\Models\StaffUser;
use App\Enums\UserRole;
use Database\Factories\UserFactory;

/**
 * @extends UserFactory<StaffUser>
 */
class StaffUserFactory extends UserFactory
{
    /** @var class-string<StaffUser> */
    protected $model = StaffUser::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            ...parent::definition(),
            'role' => UserRole::Staff,
        ];
    }
}
