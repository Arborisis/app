<?php

declare(strict_types=1);

namespace App\Enums;

enum ArborisisCategory: string
{
    case Birds = 'birds';
    case Forest = 'forest';
    case Water = 'water';
    case Insects = 'insects';
    case Wind = 'wind';
    case NightAmbience = 'night_ambience';
    case MeetingPoint = 'meeting_point';
    case QuietSpot = 'quiet_spot';
    case EducationalZone = 'educational_zone';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Birds => 'Oiseaux',
            self::Forest => 'Forêt',
            self::Water => 'Eau',
            self::Insects => 'Insectes',
            self::Wind => 'Vent',
            self::NightAmbience => 'Ambiance nocturne',
            self::MeetingPoint => 'Point de rencontre',
            self::QuietSpot => 'Spot calme',
            self::EducationalZone => 'Zone pédagogique',
            self::Other => 'Autre',
        };
    }
}
