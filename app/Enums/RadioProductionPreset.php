<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioProductionPreset: string
{
    case PodcastIntimate = 'podcast_intimate';
    case FlashPunchy = 'flash_punchy';
    case EmissionCinematic = 'emission_cinematic';
    case DjBrief = 'dj_brief';
    case Interlude = 'interlude';

    public function label(): string
    {
        return match ($this) {
            self::PodcastIntimate => 'Podcast intime',
            self::FlashPunchy => 'Flash dynamique',
            self::EmissionCinematic => 'Émission cinématographique',
            self::DjBrief => 'Annonce DJ brève',
            self::Interlude => 'Interlude',
        };
    }

    public static function forShowType(RadioShowType $showType): self
    {
        return match ($showType) {
            RadioShowType::Podcast => self::PodcastIntimate,
            RadioShowType::Flash => self::FlashPunchy,
            RadioShowType::Emission => self::EmissionCinematic,
        };
    }
}
