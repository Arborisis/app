<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\RadioGenerationJob;
use App\Models\RadioPodcast;
use App\Services\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RadioHealthService
{
    /**
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $totalJobs = RadioGenerationJob::query()->where('created_at', '>=', now()->subDay())->count();
        $failedJobs = RadioGenerationJob::query()->where('created_at', '>=', now()->subDay())->where('status', 'failed')->count();

        return [
            'icecast' => $this->icecast(),
            'liquidsoap' => $this->liquidsoap(),
            'last_podcast_generation' => RadioPodcast::query()->latest('created_at')->value('created_at')?->toIso8601String(),
            'generation_failure_rate_24h' => $totalJobs > 0 ? round($failedJobs / $totalJobs, 4) : 0.0,
            'circuit_breakers' => [
                'elevenlabs' => app(CircuitBreaker::class)->state('elevenlabs'),
                'openrouter' => app(CircuitBreaker::class)->state('openrouter'),
            ],
            'active_listeners' => app(ListenerSessionTracker::class)->activeCount(),
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function icecast(): array
    {
        $url = rtrim((string) config('radio.icecast_base_url', 'http://127.0.0.1:8000'), '/').'/status-json.xsl';

        try {
            $response = Http::timeout(3)->get($url);

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'url' => $url,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => null, 'url' => $url, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function liquidsoap(): array
    {
        $status = Cache::get(RadioStateService::CACHE_KEY_STATUS, []);
        $lastSeen = is_array($status) ? ($status['last_seen_at'] ?? null) : null;

        return [
            'ok' => $lastSeen !== null && now()->diffInMinutes(\Carbon\Carbon::parse($lastSeen)) < 5,
            'last_seen_at' => $lastSeen,
            'source' => is_array($status) ? ($status['source'] ?? null) : null,
        ];
    }
}
