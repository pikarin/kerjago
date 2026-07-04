<?php

namespace App\Http\Controllers\Employer;

use App\Actions\Profiles\UpsertEmployerProfile;
use App\Enums\Country;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertEmployerProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployerProfileController extends Controller
{
    /**
     * Show the company profile form.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();
        $profile = $user->employerProfile;

        return Inertia::render('employer/profile/Edit', [
            'profile' => $profile === null ? null : [
                'company_name' => $profile->company_name,
                'industry' => $profile->industry,
                'country' => $profile->country,
                'city' => $profile->city,
                'website' => $profile->website,
            ],
            'countries' => Country::cases(),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Create or update the company profile.
     */
    public function update(
        UpsertEmployerProfileRequest $request,
        UpsertEmployerProfile $upsertEmployerProfile,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        /** @var array{company_name: string, industry: string, country: string, city: string, website: string|null} $data */
        $data = $request->validated();

        $upsertEmployerProfile->handle($user, $data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Company profile saved.')]);

        return to_route('employer.profile.edit');
    }
}
