<?php

declare(strict_types=1);

namespace App\Enums;

enum SoundVisibility: string
{
    case Public = 'public';
    case Followers = 'followers';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Followers => 'Abonnés uniquement',
            self::Private => 'Privé',
        };
    }
}
