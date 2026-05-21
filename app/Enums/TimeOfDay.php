<?php

declare(strict_types=1);

namespace App\Enums;

enum TimeOfDay: string
{
    case Dawn = 'dawn';
    case Morning = 'morning';
    case Midday = 'midday';
    case Evening = 'evening';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Dawn => 'Aube',
            self::Morning => 'Matin',
            self::Midday => 'Midi',
            self::Evening => 'Soir',
            self::Night => 'Nuit',
        };
    }

    public static function fromHour(int $hour): self
    {
        return match (true) {
            $hour >= 5 && $hour < 7 => self::Dawn,
            $hour >= 7 && $hour < 12 => self::Morning,
            $hour >= 12 && $hour < 17 => self::Midday,
            $hour >= 17 && $hour < 21 => self::Evening,
            default => self::Night,
        };
    }
}
