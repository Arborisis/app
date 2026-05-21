<?php

declare(strict_types=1);

namespace App\Enums;

enum FrequencyScale: string
{
    case LINEAR = 'linear';
    case LOG = 'log';
    case MEL = 'mel';
}
