<?php

/**
 * Admingo is the internal staff bounded context. Unlike app/Chat, it is not
 * generic and it is not extractable — an admin panel exists to consume the
 * domain, so it imports App\Models freely. The boundary therefore runs the
 * other way, and these tests are the only thing holding it:
 *
 *   1. the domain must not depend on Admingo
 *   2. Filament and Livewire must not leak out of Admingo
 *
 * Because App\Admingo\Models\StaffUser carries every Filament contract, rule 2
 * needs no allow-list. If it ever fails, move the coupling into StaffUser
 * rather than adding an exception here. See docs/adr/0011.
 *
 * Written as `expect(dependency)->not->toBeUsedIn(domain)` rather than
 * `expect(domain)->not->toUse(dependency)`: the latter passes vacuously when
 * given an array of namespaces, which was verified by planting a Filament
 * import in App\Support and watching it go green.
 */
$domain = [
    'App\Actions',
    'App\Chat',
    'App\Concerns',
    'App\Console',
    'App\Enums',
    'App\Http',
    'App\Jobs',
    'App\Models',
    'App\Policies',
    'App\Providers',
    'App\Support',
];

arch('the domain does not depend on Admingo')
    ->expect('App\Admingo')
    ->not->toBeUsedIn($domain);

arch('Filament does not leak out of Admingo')
    ->expect('Filament')
    ->not->toBeUsedIn($domain);

arch('Livewire does not leak out of Admingo')
    ->expect('Livewire')
    ->not->toBeUsedIn($domain);
