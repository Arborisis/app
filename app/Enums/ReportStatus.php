<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Reviewing => 'En cours d\'examen',
            self::Resolved => 'Résolu',
            self::Dismissed => 'Rejeté',
        };
    }
}
