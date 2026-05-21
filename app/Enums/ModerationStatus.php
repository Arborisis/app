<?php

declare(strict_types=1);

namespace App\Enums;

enum ModerationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Hidden = 'hidden';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Approved => 'Approuvé',
            self::Rejected => 'Rejeté',
            self::Hidden => 'Masqué',
            self::Archived => 'Archivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Hidden => 'gray',
            self::Archived => 'secondary',
        };
    }
}
