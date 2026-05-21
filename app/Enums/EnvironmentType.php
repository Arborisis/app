<?php

declare(strict_types=1);

namespace App\Enums;

enum EnvironmentType: string
{
    case Forest = 'forest';
    case Ocean = 'ocean';
    case Mountain = 'mountain';
    case River = 'river';
    case Rain = 'rain';
    case Dawn = 'dawn';
    case Dusk = 'dusk';
    case Night = 'night';
    case UrbanNature = 'urban_nature';
    case Wetland = 'wetland';
    case Desert = 'desert';
    case Meadow = 'meadow';

    public function label(): string
    {
        return match ($this) {
            self::Forest => 'Forêt',
            self::Ocean => 'Océan',
            self::Mountain => 'Montagne',
            self::River => 'Rivière',
            self::Rain => 'Pluie',
            self::Dawn => 'Aube',
            self::Dusk => 'Crépuscule',
            self::Night => 'Nuit',
            self::UrbanNature => 'Nature urbaine',
            self::Wetland => 'Zone humide',
            self::Desert => 'Désert',
            self::Meadow => 'Prairie',
        };
    }
}
