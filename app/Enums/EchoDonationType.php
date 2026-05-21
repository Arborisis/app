<?php

declare(strict_types=1);

namespace App\Enums;

enum EchoDonationType: string
{
    case Tip = 'tip';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Tip => 'Pourboire',
            self::Support => 'Soutien',
        };
    }
}
