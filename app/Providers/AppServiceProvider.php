<?php

namespace App\Providers;

use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Contracts\ContextResolver;
use App\Chat\Contracts\ParticipantResolver;
use App\Support\Chat\DomainContextResolver;
use App\Support\Chat\EloquentParticipantResolver;
use App\Support\Chat\PolicyChatAuthorizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->bindChatBoundary();
    }

    /**
     * The chat module defines these contracts and the host implements them.
     *
     * Bound here rather than in ChatServiceProvider on purpose: the module must
     * not reference App\Support\Chat\*, or the dependency would point from the
     * extractable module back into the application it is meant to be lifted
     * out of. App\Chat\ChatServiceProvider only loads migrations.
     */
    private function bindChatBoundary(): void
    {
        $this->app->bind(ParticipantResolver::class, EloquentParticipantResolver::class);
        $this->app->bind(ContextResolver::class, DomainContextResolver::class);
        $this->app->bind(ChatAuthorizer::class, PolicyChatAuthorizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
