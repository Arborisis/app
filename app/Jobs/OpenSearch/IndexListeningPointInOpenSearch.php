<?php

declare(strict_types=1);

namespace App\Jobs\OpenSearch;

use App\Models\ListeningPoint;
use App\Services\OpenSearch\OpenSearchIndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexListeningPointInOpenSearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [30, 120, 300];

    public function __construct(private readonly int $listeningPointId) {}

    public function handle(OpenSearchIndexService $service): void
    {
        $point = ListeningPoint::findOrFail($this->listeningPointId);

        if (! $point->isApproved()) {
            return;
        }

        $service->indexListeningPoint($point);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('opensearch')->error("Indexation failed for listening point {$this->listeningPointId}", [
            'error' => $exception->getMessage(),
        ]);
    }
}
