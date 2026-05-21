<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactTicketCategory: string
{
    case General = 'general';
    case Bug = 'bug';
    case FeatureRequest = 'feature_request';
    case Account = 'account';
    case Billing = 'billing';
    case Privacy = 'privacy';
    case Audio = 'audio';
    case Map = 'map';

    public function label(): string
    {
        return match ($this) {
            self::General => 'Général',
            self::Bug => 'Bug / Problème technique',
            self::FeatureRequest => 'Suggestion / Fonctionnalité',
            self::Account => 'Compte utilisateur',
            self::Billing => 'Paiement / ECHO',
            self::Privacy => 'Confidentialité / RGPD',
            self::Audio => 'Upload / Audio',
            self::Map => 'Carte / Géolocalisation',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::General => 'gray',
            self::Bug => 'danger',
            self::FeatureRequest => 'success',
            self::Account => 'info',
            self::Billing => 'warning',
            self::Privacy => 'primary',
            self::Audio => 'secondary',
            self::Map => 'success',
        };
    }
}
