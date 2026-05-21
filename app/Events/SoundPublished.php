<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sound;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoundPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Sound $sound
    ) {}
}
