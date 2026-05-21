<?php

declare(strict_types=1);

namespace App\Enums;

enum MedalCategory: string
{
    case General = 'general';
    case Exploration = 'exploration';
    case Creation = 'creation';
    case Respect = 'respect';
    case Community = 'community';
    case Special = 'special';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Général',
            self::Exploration => 'Exploration',
            self::Creation => 'Création',
            self::Respect => 'Respect',
            self::Community => 'Communauté',
            self::Special => 'Spécial',
        };
    }
}
