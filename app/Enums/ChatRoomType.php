<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatRoomType: string
{
    case Public = 'public';
    case AdminOnly = 'admin_only';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::AdminOnly => 'Réservé admin',
        };
    }
}
