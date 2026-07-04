<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileIsComplete
{
    /**
     * Gate profile-dependent actions: jobseekers can't apply and employers
     * can't post or manage jobs until their profile exists. Navigation is
     * never blocked — the user is redirected to the profile form instead.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isEmployer() && $user->employerProfile === null) {
            return redirect()
                ->route('employer.profile.edit')
                ->with('status', __('Set up your company profile to continue.'));
        }

        if ($user->isJobseeker() && $user->jobseekerProfile === null) {
            return redirect()
                ->route('jobseeker.profile.edit')
                ->with('status', __('Complete your profile to continue.'));
        }

        return $next($request);
    }
}
