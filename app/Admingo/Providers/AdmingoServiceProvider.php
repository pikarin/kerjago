<?php

namespace App\Admingo\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Admingo owns its own schema, the same way App\Chat does. Keeping the panel's
 * multi-factor credentials in a module-owned migration is what stops Filament
 * vocabulary from landing in the domain `users` table — see docs/adr/0011.
 */
class AdmingoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
