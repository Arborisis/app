<?php

declare(strict_types=1);

namespace App\Enums;

enum VisitStatus: string
{
    case Valid = 'valid';
    case Invalid = 'invalid';
    case Suspicious = 'suspicious';
    case UnderReview = 'under_review';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valide',
            self::Invalid => 'Invalide',
            self::Suspicious => 'Suspect',
            self::UnderReview => 'En révision',
        };
    }
}
