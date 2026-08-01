<?php

namespace App\Admingo\Providers;

use App\Admingo\Http\Middleware\EnsureStaffUser;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdmingoPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admingo')
            ->path('admingo')
            ->brandName('Admingo')

            // Its own guard, so a marketplace session does not grant panel
            // access and vice versa. See docs/adr/0011.
            ->authGuard('admingo')

            // Admingo authenticates on its own login page rather than through
            // Fortify, which means none of Fortify's pipeline runs here — no
            // Fortify 2FA challenge, no passkey. Required multi-factor below is
            // what closes that gap. Do not relax `isRequired` on the assumption
            // that Fortify still covers this surface: it does not.
            ->login()
            ->profile()
            ->multiFactorAuthentication([
                AppAuthentication::make()->recoverable(),
            ], isRequired: true)

            // Deliberately absent: ->registration() (ADR 0010 — staff is never
            // self-assignable), ->passwordReset() and ->emailVerification().
            // Staff reset passwords through the marketplace flow, against the
            // same `users` table, so there is no second reset surface.

            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Admingo/Resources'), for: 'App\Admingo\Resources')
            ->discoverPages(in: app_path('Admingo/Pages'), for: 'App\Admingo\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Admingo/Widgets'), for: 'App\Admingo\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            // authMiddleware() replaces rather than appends, so Filament's own
            // Authenticate has to be listed alongside ours. isPersistent keeps
            // the staff check running on Livewire updates, not just page loads.
            ->authMiddleware([
                Authenticate::class,
                EnsureStaffUser::class,
            ], isPersistent: true);
    }
}
