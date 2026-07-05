<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum WorkArrangement: string
{
    use HasOptions;

    case Onsite = 'onsite';
    case Hybrid = 'hybrid';
    case Remote = 'remote';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'On-site',
            self::Hybrid => 'Hybrid',
            self::Remote => 'Remote',
        };
    }
}
