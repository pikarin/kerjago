<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum SalaryPeriod: string
{
    use HasOptions;

    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Per month',
            self::Yearly => 'Per year',
            self::Hourly => 'Per hour',
        };
    }
}
