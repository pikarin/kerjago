<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Language: string
{
    use HasOptions;

    case Indonesian = 'id';
    case English = 'en';
    case Malay = 'ms';
    case Mandarin = 'zh';
    case Thai = 'th';
    case Vietnamese = 'vi';
    case Tagalog = 'tl';

    public function label(): string
    {
        return match ($this) {
            self::Indonesian => 'Indonesian',
            self::English => 'English',
            self::Malay => 'Malay',
            self::Mandarin => 'Mandarin',
            self::Thai => 'Thai',
            self::Vietnamese => 'Vietnamese',
            self::Tagalog => 'Tagalog',
        };
    }
}
