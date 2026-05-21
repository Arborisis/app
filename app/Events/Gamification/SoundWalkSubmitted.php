<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\SoundWalk;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoundWalkSubmitted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SoundWalk $soundWalk,
    ) {
    }
}
