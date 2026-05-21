<?php

declare(strict_types=1);

namespace App\Enums;

enum ListeningPointVersionEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatsRefreshed = 'stats_refreshed';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Création',
            self::Updated => 'Modification',
            self::StatsRefreshed => 'Statistiques recalculées',
            self::Deleted => 'Suppression',
        };
    }
}
