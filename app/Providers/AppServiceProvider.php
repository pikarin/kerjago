<?php

namespace App\Providers;

use App\Actions\Unlocks\ResolveUnlockedProfileIds;
use App\Chat\Contracts\ChatAuthorizer;
use App\Chat\Contracts\ContextResolver;
use App\Chat\Contracts\ParticipantResolver;
use App\Chat\Models\Conversation;
use App\Policies\ConversationPolicy;
use App\Support\Chat\DomainContextResolver;
use App\Support\Chat\EloquentParticipantResolver;
use App\Support\Chat\PolicyChatAuthorizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

        // Scoped, not bound: the Action memoizes unlock lookups, and one
        // instance per request is what turns "a query per candidate card" into
        // one query per page.
        $this->app->scoped(ResolveUnlockedProfileIds::class);
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

        // Policy auto-discovery maps App\Models\{Model} to
        // App\Policies\{Model}Policy. Conversation lives in App\Chat\Models, so
        // it has to be registered by hand or every Gate check silently denies.
        Gate::policy(Conversation::class, ConversationPolicy::class);
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

        // Surfaces accidental lazy loads outside production. Note it does not
        // catch every case: Eloquent only arms the per-instance flag when a
        // query hydrates more than one row, so a route-bound model is exempt.
        Model::preventLazyLoading(! app()->isProduction());

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
