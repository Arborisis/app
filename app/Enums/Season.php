<?php

declare(strict_types=1);

namespace App\Enums;

enum Season: string
{
    case Spring = 'spring';
    case Summer = 'summer';
    case Autumn = 'autumn';
    case Winter = 'winter';

    public function label(): string
    {
        return match ($this) {
            self::Spring => 'Printemps',
            self::Summer => 'Été',
            self::Autumn => 'Automne',
            self::Winter => 'Hiver',
        };
    }

    public static function fromDate(\DateTimeInterface $date): self
    {
        $month = (int) $date->format('n');

        return match (true) {
            $month >= 3 && $month <= 5 => self::Spring,
            $month >= 6 && $month <= 8 => self::Summer,
            $month >= 9 && $month <= 11 => self::Autumn,
            default => self::Winter,
        };
    }
}
