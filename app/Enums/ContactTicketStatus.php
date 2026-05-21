<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactTicketStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Spam = 'spam';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::InProgress => 'En cours',
            self::Resolved => 'Résolu',
            self::Spam => 'Spam',
        };
    }
}
