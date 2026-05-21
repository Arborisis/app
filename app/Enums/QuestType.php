<?php

declare(strict_types=1);

namespace App\Enums;

enum QuestType: string
{
    case Discovery = 'discovery';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Seasonal = 'seasonal';
    case Community = 'community';
    case Creation = 'creation';
    case Listening = 'listening';
    case Contribution = 'contribution';
    case NatureRespect = 'nature_respect';

    public function label(): string
    {
        return match ($this) {
            self::Discovery => 'Découverte',
            self::Daily => 'Journalière',
            self::Weekly => 'Hebdomadaire',
            self::Seasonal => 'Saisonnière',
            self::Community => 'Communautaire',
            self::Creation => 'Création',
            self::Listening => 'Écoute',
            self::Contribution => 'Contribution',
            self::NatureRespect => 'Respect de la nature',
        };
    }
}
