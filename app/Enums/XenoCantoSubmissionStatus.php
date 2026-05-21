<?php

declare(strict_types=1);

namespace App\Enums;

enum XenoCantoSubmissionStatus: string
{
    case Prepared = 'prepared';
    case Submitted = 'submitted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Prepared => 'Pret pour xeno-canto',
            self::Submitted => 'Soumis sur xeno-canto',
            self::Rejected => 'Refuse ou abandonne',
        };
    }
}
