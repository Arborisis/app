<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\ArborisisPoint;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArborisisPointSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public ArborisisPoint $point)
    {
    }
}
