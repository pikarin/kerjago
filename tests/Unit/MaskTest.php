<?php

use App\Support\Masking\Mask;

test('a name keeps its first word and reduces the rest to initials', function (?string $raw, ?string $masked) {
    expect(Mask::name($raw))->toBe($masked);
})->with([
    'two words' => ['Budi Santoso', 'Budi S.'],
    'three words' => ['Budi Santoso Wijaya', 'Budi S. W.'],
    // Nothing to withhold, and padding it would invent a surname.
    'single word' => ['Budi', 'Budi'],
    'extra whitespace' => ['  Budi   Santoso  ', 'Budi S.'],
    'lowercase surname' => ['budi santoso', 'budi S.'],
    'null' => [null, null],
    'blank' => ['   ', null],
]);

test('a masked email leaks neither the local part, the domain nor the length', function () {
    expect(Mask::email('budi.santoso.wijaya@ajobthing.com'))->toBe('•••••@•••••.•••••')
        ->and(Mask::email('a@b.co'))->toBe('•••••@•••••.•••••')
        ->and(Mask::email(null))->toBeNull()
        ->and(Mask::email(''))->toBeNull();
});

test('a masked phone number keeps nothing, not even the country code', function () {
    expect(Mask::phone('+62 812 3456 7890'))->toBe('•••••')
        ->and(Mask::phone('081234'))->toBe('•••••')
        ->and(Mask::phone(null))->toBeNull();
});

test('masks are the same length regardless of input', function () {
    expect(Mask::phone('+628123456789012345'))->toBe(Mask::phone('0812'))
        ->and(Mask::email('a@b.co'))->toBe(Mask::email('averylongaddress@somecompany.co.id'));
});
