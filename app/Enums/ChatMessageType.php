<?php

declare(strict_types=1);

namespace App\Enums;

enum ChatMessageType: string
{
    case Text = 'text';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texte',
            self::System => 'Système',
        };
    }
}
