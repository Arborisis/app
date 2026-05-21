<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Claimed = 'claimed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Non commencée',
            self::InProgress => 'En cours',
            self::Completed => 'Terminée',
            self::Claimed => 'Réclamée',
            self::Expired => 'Expirée',
        };
    }
}
