<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactTicketReplySource: string
{
    case Team = 'team';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Team => 'Équipe Arborisis',
            self::Customer => 'Utilisateur',
        };
    }
}
