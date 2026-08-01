<?php

namespace App\Admingo\Http\Middleware;

use App\Admingo\Models\StaffUser;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The panel's third access gate, behind the StaffUser global scope and
 * canAccessPanel(). Registered with `isPersistent: true` so it also runs on
 * Livewire update requests rather than only on the initial page load.
 *
 * App\Http\Middleware\EnsureUserHasRole cannot be reused here: it reads
 * $request->user(), which resolves the default `web` guard, and Admingo runs
 * on its own. Resolving through Filament::auth() keeps this correct if the
 * panel's guard is ever renamed.
 */
class EnsureStaffUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Filament::auth()->user();

        abort_unless($user instanceof StaffUser && $user->isStaff(), 403);

        return $next($request);
    }
}
