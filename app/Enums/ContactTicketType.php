<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactTicketType: string
{
    case Contact = 'contact';
    case Privacy = 'privacy';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact général',
            self::Privacy => 'Données personnelles / RGPD',
            self::Support => 'Support technique',
        };
    }

    public function recipientEmail(): string
    {
        return match ($this) {
            self::Contact => 'contact@arborisis.com',
            self::Privacy => 'privacy@arborisis.com',
            self::Support => 'contact@arborisis.com',
        };
    }
}
