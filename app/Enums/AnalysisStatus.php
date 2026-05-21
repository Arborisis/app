<?php

declare(strict_types=1);

namespace App\Enums;

enum AnalysisStatus: string
{
    case PENDING = 'pending';
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
}
