<?php

declare(strict_types=1);

namespace App\Enums;

enum TodoStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Completed => 'Terminée',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }
}
