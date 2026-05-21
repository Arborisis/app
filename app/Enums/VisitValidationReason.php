<?php

declare(strict_types=1);

namespace App\Enums;

enum VisitValidationReason: string
{
    case WithinRange = 'within_range';
    case TooFar = 'too_far';
    case PoorAccuracy = 'poor_accuracy';
    case CooldownActive = 'cooldown_active';
    case DailyLimitReached = 'daily_limit_reached';
    case ImpossibleSpeed = 'impossible_speed';
    case PointNotApproved = 'point_not_approved';
    case LocationConsentMissing = 'location_consent_missing';

    public function label(): string
    {
        return match ($this) {
            self::WithinRange => 'Dans le rayon',
            self::TooFar => 'Trop loin',
            self::PoorAccuracy => 'Précision GPS insuffisante',
            self::CooldownActive => 'Cooldown actif',
            self::DailyLimitReached => 'Limite journalière atteinte',
            self::ImpossibleSpeed => 'Vitesse impossible',
            self::PointNotApproved => 'Point non approuvé',
            self::LocationConsentMissing => 'Consentement géolocalisation manquant',
        };
    }
}
