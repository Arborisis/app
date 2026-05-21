<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    /**
     * Health check complet pour Uptime Kuma et monitoring externe.
     *
     * Vérifie : PostgreSQL, Redis, S3 (Contabo), espace disque.
     */
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'disk' => $this->checkDiskSpace(),
        ];

        $allHealthy = !in_array(false, array_column($checks, 'healthy'), true);

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $allHealthy ? 200 : 503);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['healthy' => true, 'latency_ms' => $this->measureLatency(fn () => DB::select('SELECT 1'))];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkRedis(): array
    {
        try {
            Cache::store('redis')->put('health_check', time(), 10);
            $value = Cache::store('redis')->get('health_check');

            return ['healthy' => $value !== null, 'latency_ms' => $this->measureLatency(fn () => Cache::store('redis')->get('health_check'))];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkStorage(): array
    {
        try {
            $disk = Storage::disk(config('filesystems.default', 'local'));
            $disk->put('health-check.txt', 'ok');
            $content = $disk->get('health-check.txt');
            $disk->delete('health-check.txt');

            return ['healthy' => $content === 'ok', 'disk' => config('filesystems.default', 'local')];
        } catch (\Throwable $e) {
            return ['healthy' => false, 'error' => $e->getMessage()];
        }
    }

    private function checkDiskSpace(): array
    {
        $path = storage_path();
        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $usedPercent = $total > 0 ? round((($total - $free) / $total) * 100, 2) : 0;

        return [
            'healthy' => $usedPercent < 90,
            'free_gb' => round($free / 1024 / 1024 / 1024, 2),
            'total_gb' => round($total / 1024 / 1024 / 1024, 2),
            'used_percent' => $usedPercent,
        ];
    }

    private function measureLatency(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}
