<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoundAnalyzed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Sound $sound,
        public readonly SoundAnalysis $analysis,
    ) {}
}
