<?php

declare(strict_types=1);

namespace App\Enums;

enum SoundIdeaStatus: string
{
    case Pending = 'pending';
    case Completed = 'completed';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Completed => 'Complété',
            self::Dismissed => 'Ignoré',
        };
    }
}
