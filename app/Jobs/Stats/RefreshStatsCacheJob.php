<?php

declare(strict_types=1);

namespace App\Jobs\Stats;

use App\Services\Stats\StatsCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshStatsCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $backoff = [60, 300];

    public function handle(StatsCacheService $cache): void
    {
        Log::info('Refreshing stats cache...');
        $cache->refreshAll();
        Log::info('Stats cache refreshed.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Stats cache refresh failed', ['error' => $exception->getMessage()]);
    }
}
