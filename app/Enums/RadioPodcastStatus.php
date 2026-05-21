<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioPodcastStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Validating = 'validating';
    case Published = 'published';
    case Failed = 'failed';
    case Rejected = 'rejected';
}
