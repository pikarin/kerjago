<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Availability: string
{
    use HasOptions;

    case Immediately = 'immediately';
    case TwoWeeks = 'two_weeks';
    case OneMonth = 'one_month';
    case TwoMonthsPlus = 'two_months_plus';

    public function label(): string
    {
        return match ($this) {
            self::Immediately => 'Immediately',
            self::TwoWeeks => 'In 2 weeks',
            self::OneMonth => 'In 1 month',
            self::TwoMonthsPlus => 'In 2+ months',
        };
    }
}
