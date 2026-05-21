<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioJinglePlacement: string
{
    case BeforeTrack = 'before_track';
    case AfterTrack = 'after_track';
    case BetweenBlocks = 'between_blocks';
    case Hourly = 'hourly';

    public function label(): string
    {
        return match ($this) {
            self::BeforeTrack => 'Avant un son',
            self::AfterTrack => 'Après un son',
            self::BetweenBlocks => 'Entre deux blocs',
            self::Hourly => 'Toutes les heures',
        };
    }
}
