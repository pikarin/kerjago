<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ExperienceLevel: string
{
    use HasOptions;

    case Entry = 'entry';
    case Mid = 'mid';
    case Senior = 'senior';
    case Lead = 'lead';

    public function label(): string
    {
        return match ($this) {
            self::Entry => 'Entry level',
            self::Mid => 'Mid level',
            self::Senior => 'Senior',
            self::Lead => 'Lead',
        };
    }
}
