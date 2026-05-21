<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioScheduleRepeat: string
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::None => 'Aucune',
            self::Daily => 'Chaque jour',
            self::Weekly => 'Chaque semaine',
            self::Monthly => 'Chaque mois',
        };
    }
}
