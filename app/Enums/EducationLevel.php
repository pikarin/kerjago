<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum EducationLevel: string
{
    use HasOptions;

    case None = 'none';
    case HighSchool = 'high_school';
    case Diploma = 'diploma';
    case Bachelor = 'bachelor';
    case Master = 'master';
    case Doctorate = 'doctorate';

    public function label(): string
    {
        return match ($this) {
            self::None => 'No requirement',
            self::HighSchool => 'High school',
            self::Diploma => 'Diploma',
            self::Bachelor => "Bachelor's degree",
            self::Master => "Master's degree",
            self::Doctorate => 'Doctorate',
        };
    }
}
