<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactTicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Medium => 'Moyenne',
            self::High => 'Haute',
            self::Urgent => 'Urgente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'gray',
            self::Medium => 'info',
            self::High => 'warning',
            self::Urgent => 'danger',
        };
    }
}
