<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\AnalysisStatus;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Services\AudioAnalysis\AudioAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAudioAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        public int $soundId,
        public array $config = [],
    ) {}

    public function handle(AudioAnalysisService $service): void
    {
        $sound = Sound::with('soundFile')->find($this->soundId);

        if (! $sound) {
            return;
        }

        $analysis = SoundAnalysis::firstOrCreate(
            ['sound_id' => $sound->id],
            ['status' => AnalysisStatus::PENDING]
        );

        try {
            $service->analyze($sound, $this->config);
        } catch (\Throwable $e) {
            $analysis->markFailed($e->getMessage());
            throw $e;
        }
    }
}
