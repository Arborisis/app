<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AudioAnalysisCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SoundAnalysis $analysis,
        public Sound $sound,
    ) {}
}
