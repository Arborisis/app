<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum RadioDaypart: string
{
    case Dawn = 'dawn';
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Dawn => 'Aube (5h-9h)',
            self::Morning => 'Matinée (9h-12h)',
            self::Afternoon => 'Après-midi (12h-17h)',
            self::Evening => 'Soirée (17h-21h)',
            self::Night => 'Nuit (21h-5h)',
        };
    }

    public static function fromHour(int $hour): self
    {
        return match (true) {
            $hour >= 5 && $hour < 9 => self::Dawn,
            $hour >= 9 && $hour < 12 => self::Morning,
            $hour >= 12 && $hour < 17 => self::Afternoon,
            $hour >= 17 && $hour < 21 => self::Evening,
            default => self::Night,
        };
    }

    public static function fromCarbon(CarbonInterface $moment): self
    {
        return self::fromHour((int) $moment->format('G'));
    }
}
