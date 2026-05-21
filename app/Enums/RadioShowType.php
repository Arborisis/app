<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioShowType: string
{
    case Podcast  = 'podcast';
    case Flash    = 'flash';
    case Emission = 'emission';
}
