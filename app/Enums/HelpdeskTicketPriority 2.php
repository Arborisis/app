<?php

declare(strict_types=1);

namespace App\Enums;

enum HelpdeskTicketPriority: string
{
    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Basse',
            self::Normal => 'Normale',
            self::High => 'Haute',
            self::Critical => 'Critique',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'sage',
            self::Normal => 'cyan',
            self::High => 'amber',
            self::Critical => 'red',
        };
    }

    public function urgencyScore(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Normal => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}
