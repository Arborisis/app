<?php

declare(strict_types=1);

namespace App\Enums;

enum SoundStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Published = 'published';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Pending => 'En attente',
            self::Published => 'Publié',
            self::Hidden => 'Masqué',
        };
    }

    public function isPublic(): bool
    {
        return $this === self::Published;
    }
}
