<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Visitor = 'visitor';
    case User = 'user';
    case Creator = 'creator';
    case Moderator = 'moderator';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Visitor => 'Visiteur',
            self::User => 'Utilisateur',
            self::Creator => 'Créateur',
            self::Moderator => 'Modérateur',
            self::Admin => 'Administrateur',
        };
    }

    public function canUpload(): bool
    {
        return in_array($this, [self::User, self::Creator, self::Moderator, self::Admin], true);
    }

    public function canAccessAdmin(): bool
    {
        return in_array($this, [self::Moderator, self::Admin], true);
    }
}
