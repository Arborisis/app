<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Radio\RadioAudioCacheService;
use App\Services\Radio\RadioStateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class RadioHealthCheckCommand extends Command
{
    protected $signature = 'radio:health-check
                            {--json : Output machine-readable JSON}';

    protected $description = 'Check radio pipeline health (cache, Icecast, Liquidsoap, podcast pipeline)';

    public function handle(RadioAudioCacheService $cache): int
    {
        $checks = [];
        $exitCode = 0;

        // 1. Cache disk writable
        $disk = config('radio.audio_cache.disk', 'radio_cache');
        $diskRoot = Storage::disk($disk)->path('');
        $cacheWritable = is_dir($diskRoot) && is_writable($diskRoot);
        $checks[] = [
            'name' => 'Cache disk writable',
            'status' => $cacheWritable ? 'ok' : 'critical',
            'detail' => $diskRoot,
        ];
        if (! $cacheWritable) {
            $exitCode = 2;
        }

        // 2. Cached sounds count
        $cachedSounds = count(glob($diskRoot . 'sounds/*.mp3') ?: []);
        $checks[] = [
            'name' => 'Cached sounds',
            'status' => $cachedSounds > 0 ? 'ok' : 'warning',
            'detail' => "{$cachedSounds} sounds in cache",
        ];
        if ($cachedSounds === 0 && $exitCode < 1) {
            $exitCode = 1;
        }

        // 3. Icecast status
        $icecastBaseUrl = config('radio.icecast_base_url', 'http://127.0.0.1:8000');
        $icecastStatusUrl = rtrim($icecastBaseUrl, '/') . '/status-json.xsl';
        try {
            $icecastResponse = Http::timeout(5)->get($icecastStatusUrl);
            $icecastOk = $icecastResponse->successful();
        } catch (\Throwable) {
            $icecastOk = false;
        }
        $checks[] = [
            'name' => 'Icecast reachable',
            'status' => $icecastOk ? 'ok' : 'critical',
            'detail' => $icecastStatusUrl,
        ];
        if (! $icecastOk) {
            $exitCode = 2;
        }

        // 4. Liquidsoap last seen
        $status = Cache::get(RadioStateService::CACHE_KEY_STATUS, []);
        $lastSeen = $status['last_seen_at'] ?? null;
        $liquidsoapOk = $lastSeen && now()->diffInMinutes(\Carbon\Carbon::parse($lastSeen)) < 5;
        $checks[] = [
            'name' => 'Liquidsoap heartbeat',
            'status' => $liquidsoapOk ? 'ok' : 'warning',
            'detail' => $lastSeen ?? 'never',
        ];
        if (! $liquidsoapOk && $exitCode < 1) {
            $exitCode = 1;
        }

        // 5. Podcast pipeline keys
        $openRouterKey = ! empty(config('services.openrouter.api_key'));
        $elevenlabsKey = ! empty(config('services.elevenlabs.api_key'));
        $podcastOk = $openRouterKey && $elevenlabsKey;
        $checks[] = [
            'name' => 'Podcast pipeline configured',
            'status' => $podcastOk ? 'ok' : 'warning',
            'detail' => 'OpenRouter: ' . ($openRouterKey ? 'yes' : 'no') . ', ElevenLabs: ' . ($elevenlabsKey ? 'yes' : 'no'),
        ];
        if (! $podcastOk && $exitCode < 1) {
            $exitCode = 1;
        }

        // 6. Recent podcast failures
        $failedCount = \App\Models\RadioPodcast::query()
            ->where('status', \App\Enums\RadioPodcastStatus::Failed)
            ->where('failed_at', '>=', now()->subDay())
            ->count();
        $failuresOk = $failedCount < 5;
        $checks[] = [
            'name' => 'Recent podcast failures (24h)',
            'status' => $failuresOk ? 'ok' : 'warning',
            'detail' => "{$failedCount} failures",
        ];
        if (! $failuresOk && $exitCode < 1) {
            $exitCode = 1;
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'healthy' => $exitCode === 0,
                'exit_code' => $exitCode,
                'checks' => $checks,
            ]));

            return $exitCode;
        }

        $headers = ['Check', 'Status', 'Detail'];
        $rows = [];
        foreach ($checks as $check) {
            $status = match ($check['status']) {
                'ok' => '<info>OK</info>',
                'warning' => '<comment>WARNING</comment>',
                'critical' => '<error>CRITICAL</error>',
                default => $check['status'],
            };
            $rows[] = [$check['name'], $status, $check['detail']];
        }

        $this->table($headers, $rows);

        return $exitCode;
    }
}
