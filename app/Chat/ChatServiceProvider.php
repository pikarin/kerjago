<?php

namespace App\Chat;

use Illuminate\Support\ServiceProvider;

/**
 * The module's own provider. It deliberately does NOT bind the boundary
 * contracts to implementations: those live in the host application, and
 * referencing them here would point the dependency the wrong way and break
 * extraction. Bindings are registered in App\Providers\AppServiceProvider.
 */
class ChatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
