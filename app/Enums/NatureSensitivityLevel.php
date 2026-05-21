<?php

declare(strict_types=1);

namespace App\Enums;

enum NatureSensitivityLevel: string
{
    case Normal = 'normal';
    case Fragile = 'fragile';
    case SensitiveSpecies = 'sensitive_species';
    case Private = 'private';
    case Dangerous = 'dangerous';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::Fragile => 'Fragile',
            self::SensitiveSpecies => 'Espèce sensible',
            self::Private => 'Privé',
            self::Dangerous => 'Dangereux',
        };
    }

    public function requiresApproximateLocation(): bool
    {
        return match ($this) {
            self::Normal => false,
            self::Fragile, self::SensitiveSpecies, self::Private, self::Dangerous => true,
        };
    }

    public function warningText(): ?string
    {
        return match ($this) {
            self::Normal => null,
            self::Fragile => 'Ce lieu est fragile : sa position exacte est protégée.',
            self::SensitiveSpecies => 'Espèce sensible présente : localisation approximative uniquement.',
            self::Private => 'Lieu privé : accès restreint et coordonnées protégées.',
            self::Dangerous => 'Zone dangereuse : localisation approximative pour votre sécurité.',
        };
    }
}
