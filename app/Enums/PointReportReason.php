<?php

declare(strict_types=1);

namespace App\Enums;

enum PointReportReason: string
{
    case InaccurateLocation = 'inaccurate_location';
    case SensitiveLocation = 'sensitive_location';
    case DangerousAccess = 'dangerous_access';
    case PrivateProperty = 'private_property';
    case Spam = 'spam';
    case Duplicate = 'duplicate';
    case InappropriateContent = 'inappropriate_content';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::InaccurateLocation => 'Localisation inexacte',
            self::SensitiveLocation => 'Lieu sensible mal protégé',
            self::DangerousAccess => 'Accès dangereux',
            self::PrivateProperty => 'Propriété privée',
            self::Spam => 'Spam',
            self::Duplicate => 'Doublon',
            self::InappropriateContent => 'Contenu inapproprié',
            self::Other => 'Autre',
        };
    }
}
