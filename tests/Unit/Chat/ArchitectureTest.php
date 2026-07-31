<?php

/**
 * app/Chat/ is meant to be liftable into its own service. The boundary is
 * convention rather than a separate composer package, so THIS TEST is the only
 * thing enforcing it. A single `use App\Models\User;` inside the module turns
 * extraction from a directory move into a rewrite.
 *
 * If this fails, do not add the dependency to the allow-list. Invert it: define
 * a contract in App\Chat\Contracts and implement it in App\Support\Chat.
 */
arch('the chat module does not depend on the host application')
    ->expect('App\Chat')
    ->not->toUse([
        'App\Models',
        'App\Enums',
        'App\Http',
        'App\Actions',
        'App\Support',
        'App\Policies',
        'App\Providers',
        'Inertia',
    ]);

arch('the chat module stays HTTP-agnostic')
    ->expect('App\Chat')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Http\Response',
    ]);

/**
 * The boundary is only invertible if it is expressed as interfaces — a
 * concrete class here would drag its implementation across with it.
 */
arch('chat boundary contracts are interfaces')
    ->expect('App\Chat\Contracts')
    ->toBeInterfaces();

/**
 * Data crossing the boundary is immutable, so nothing downstream can mutate a
 * resolved identity and have that look like it came from the host.
 */
arch('data crossing the chat boundary is readonly')
    ->expect('App\Chat\Data')
    ->toBeReadonly();
