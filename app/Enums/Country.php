<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Country: string
{
    use HasOptions;

    case Indonesia = 'ID';
    case Singapore = 'SG';
    case Malaysia = 'MY';
    case Philippines = 'PH';
    case Vietnam = 'VN';
    case Thailand = 'TH';

    public function label(): string
    {
        return match ($this) {
            self::Indonesia => 'Indonesia',
            self::Singapore => 'Singapore',
            self::Malaysia => 'Malaysia',
            self::Philippines => 'Philippines',
            self::Vietnam => 'Vietnam',
            self::Thailand => 'Thailand',
        };
    }
}
