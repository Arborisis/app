<?php

declare(strict_types=1);

namespace App\Enums;

enum MedalRarity: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Legendary = 'legendary';
    case Seasonal = 'seasonal';
    case Community = 'community';
    case Ecological = 'ecological';

    public function label(): string
    {
        return match ($this) {
            self::Bronze => 'Bronze',
            self::Silver => 'Argent',
            self::Gold => 'Or',
            self::Platinum => 'Platine',
            self::Legendary => 'Légendaire',
            self::Seasonal => 'Saisonnière',
            self::Community => 'Communautaire',
            self::Ecological => 'Écologique',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Bronze => 'amber',
            self::Silver => 'slate',
            self::Gold => 'yellow',
            self::Platinum => 'cyan',
            self::Legendary => 'violet',
            self::Seasonal => 'emerald',
            self::Community => 'blue',
            self::Ecological => 'green',
        };
    }
}
