<?php

declare(strict_types=1);

namespace App\Enums;

enum AchievementCategory: string
{
    case General = 'general';
    case Exploration = 'exploration';
    case Creation = 'creation';
    case Social = 'social';
    case Contribution = 'contribution';
    case Nature = 'nature';
    case Milestone = 'milestone';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Général',
            self::Exploration => 'Exploration',
            self::Creation => 'Création',
            self::Social => 'Social',
            self::Contribution => 'Contribution',
            self::Nature => 'Nature',
            self::Milestone => 'Jalons',
        };
    }
}
