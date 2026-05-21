<?php

declare(strict_types=1);

namespace App\Jobs\Scientific;

use App\Models\Sound;
use App\Services\Scientific\ListeningPointService;
use App\Services\Scientific\OpenMeteoEnvironmentalDataService;
use App\Services\Scientific\ScientificMetricService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ComputeScientificMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function __construct(private readonly int $soundId) {}

    public function handle(
        ScientificMetricService $metricService,
        ListeningPointService $pointService,
        OpenMeteoEnvironmentalDataService $weatherService,
    ): void {
        $sound = Sound::with(['soundAnalysis.birdnetDetections', 'tags', 'listeningPoint'])
            ->findOrFail($this->soundId);

        if (! $sound->isPublic()) {
            return;
        }

        // Enrichissement serveur uniquement avec coordonnées publiques/obfusquées.
        $weatherService->enrichSound($sound);

        // 1. Métriques du son
        $metricService->computeAllForSound($sound);

        // 2. Refresh des stats du point d'écoute
        if ($sound->listeningPoint) {
            $pointService->refreshPointStats($sound->listeningPoint);

            // 3. Métriques du point d'écoute
            $metricService->computeAllForListeningPoint($sound->listeningPoint);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('scientific')->error("Metrics computation failed for sound {$this->soundId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
