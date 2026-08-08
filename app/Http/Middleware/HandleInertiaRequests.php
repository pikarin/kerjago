<?php

namespace App\Http\Middleware;

use App\Models\EmployerProfile;
use App\Models\User;
use App\Support\Capabilities\EmployerCapabilities;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private EmployerCapabilities $capabilities) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // Resolved once and handed to both: the two props describe the same
        // profile, and asking for it twice invites a second lookup the moment
        // either of them stops going through the relation's own cache.
        $employerProfile = $this->employerProfile($request);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'capabilities' => $this->capabilities($employerProfile),
            'verification' => $this->verification($employerProfile),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * What the signed-in employer may do, keyed by capability.
     *
     * The frontend is given capabilities rather than a verification flag on
     * purpose: a `v-if` on "is verified" would be one more call site to find
     * when a second gate arrives, and it is the call site where a missed one
     * fails open.
     *
     * @return array<string, array{allowed: bool, reason: string|null}>|null
     */
    private function capabilities(?EmployerProfile $employerProfile): ?array
    {
        return $employerProfile === null ? null : $this->capabilities->map($employerProfile);
    }

    /**
     * Verification state itself, for the two surfaces that are genuinely about
     * verification rather than about a capability: the company-page banner and
     * the request button. Everything else asks the capability map.
     *
     * @return array{verified: bool, requested_at: string|null}|null
     */
    private function verification(?EmployerProfile $employerProfile): ?array
    {
        if ($employerProfile === null) {
            return null;
        }

        return [
            'verified' => $employerProfile->isVerified(),
            'requested_at' => $employerProfile->verification_requested_at?->toIso8601String(),
        ];
    }

    /**
     * Null for guests, jobseekers, staff, and employers who have not filled in
     * their company profile yet — none of them are subject to these gates.
     */
    private function employerProfile(Request $request): ?EmployerProfile
    {
        $user = $request->user();

        return $user instanceof User && $user->isEmployer()
            ? $user->employerProfile
            : null;
    }
}
