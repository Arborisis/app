<?php

declare(strict_types=1);

namespace App\Enums;

enum PresenceVisibilityMode: string
{
    case Invisible = 'invisible';
    case Approximate = 'approximate';
    case FriendsOnly = 'friends_only';
    case PublicZone = 'public_zone';

    public function label(): string
    {
        return match ($this) {
            self::Invisible => 'Invisible',
            self::Approximate => 'Approximative',
            self::FriendsOnly => 'Amis uniquement',
            self::PublicZone => 'Zone publique',
        };
    }
}
