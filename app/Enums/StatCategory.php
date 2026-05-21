<?php

declare(strict_types=1);

namespace App\Enums;

enum StatCategory: string
{
    case General = 'general';
    case Distribution = 'distribution';
    case Geographic = 'geographic';
    case Audio = 'audio';
    case Biodiversity = 'biodiversity';
    case ListeningPoints = 'listening_points';
    case Quality = 'quality';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Général',
            self::Distribution => 'Distribution',
            self::Geographic => 'Géographique',
            self::Audio => 'Audio',
            self::Biodiversity => 'Biodiversité',
            self::ListeningPoints => 'Points d\'écoute',
            self::Quality => 'Qualité',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $case): string => $case->value,
            self::cases()
        );
    }
}
