<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioListenerSessionStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Closed => 'Fermée',
            self::Expired => 'Expirée',
        };
    }
}
