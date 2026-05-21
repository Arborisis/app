<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\ArborisisVisit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuspiciousVisitDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public ArborisisVisit $visit)
    {
    }
}
