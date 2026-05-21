<?php

declare(strict_types=1);

namespace App\Enums;

enum LicenseType: string
{
    case AllRightsReserved = 'all_rights_reserved';
    case CcBy = 'cc_by';
    case CcBySa = 'cc_by_sa';
    case CcByNc = 'cc_by_nc';
    case Cc0 = 'cc0';

    public function label(): string
    {
        return match ($this) {
            self::AllRightsReserved => 'Tous droits réservés',
            self::CcBy => 'CC BY',
            self::CcBySa => 'CC BY-SA',
            self::CcByNc => 'CC BY-NC',
            self::Cc0 => 'CC0 (Domaine public)',
        };
    }
}
