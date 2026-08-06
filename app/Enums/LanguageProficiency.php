<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum LanguageProficiency: string
{
    use HasOptions;

    case Basic = 'basic';
    case Good = 'good';
    case Fluent = 'fluent';
    case Native = 'native';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Good => 'Good',
            self::Fluent => 'Fluent',
            self::Native => 'Native',
        };
    }
}
