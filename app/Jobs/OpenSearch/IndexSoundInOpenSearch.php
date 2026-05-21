<?php

declare(strict_types=1);

namespace App\Jobs\OpenSearch;

use App\Models\Sound;
use App\Services\OpenSearch\OpenSearchIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexSoundInOpenSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 300];

    public function __construct(private readonly int $soundId) {}

    public function handle(OpenSearchIndexService $service): void
    {
        $sound = Sound::with([
            'tags',
            'soundLocation.listeningPoint',
            'soundAnalysis.birdnetDetections',
            'environmentalObservation',
            'environment',
        ])->findOrFail($this->soundId);

        if (! $sound->isPublic()) {
            $service->deleteSound($sound->id);
            return;
        }

        $service->indexSound($sound);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('opensearch')->error("Indexation failed for sound {$this->soundId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
