<?php

namespace App\Admingo\Models\Scopes;

use App\Admingo\Models\StaffUser;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts App\Admingo\Models\StaffUser to staff rows of the shared `users`
 * table.
 *
 * This is the outermost of the panel's three access gates: a non-staff row is
 * not merely denied, it is unretrievable by the admingo guard, so no session
 * and no Livewire request can ever be established for one. See docs/adr/0011.
 */
/**
 * @implements Scope<StaffUser>
 */
class StaffScope implements Scope
{
    /**
     * @param  Builder<covariant StaffUser>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('role', UserRole::Staff->value);
    }
}
