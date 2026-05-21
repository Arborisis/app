<?php

declare(strict_types=1);

namespace App\Services\Stats;

use App\Enums\StatCategory;
use App\Models\Stat;
use App\Services\Scientific\ScientificStatsService;
use Illuminate\Support\Facades\Cache;

class StatsCacheService
{
    private const CACHE_TTL_MINUTES = 30;

    public function __construct(
        private readonly ScientificStatsService $statsService,
    ) {}

    /**
     * Get a stat by key. Computes and caches if missing or stale.
     *
     * @return array<string, mixed>|null
     */
    public function get(string $key, ?StatCategory $category = null): ?array
    {
        $cacheKey = "stats:{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_TTL_MINUTES), function () use ($key, $category) {
            $stat = Stat::key($key)->when($category, fn ($q) => $q->category($category))->first();

            if ($stat && ! $stat->isStale(self::CACHE_TTL_MINUTES)) {
                return $stat->value;
            }

            return $this->computeAndStore($key);
        });
    }

    /**
     * Get multiple stats at once.
     *
     * @param array<int, string> $keys
     * @return array<string, array<string, mixed>|null>
     */
    public function getMany(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    /**
     * Force refresh a specific stat.
     */
    public function refresh(string $key): array
    {
        Cache::forget("stats:{$key}");

        return $this->computeAndStore($key);
    }

    /**
     * Refresh all known stats.
     */
    public function refreshAll(): void
    {
        $keys = [
            'global',
            'category_distribution',
            'environment_distribution',
            'temporal_distribution',
            'geo_heatmap',
            'audio_features',
            'top_locations',
            'equipment_distribution',
            'species_distribution',
            'listening_points',
            'global_metrics',
            'environmental_overview',
            'quality_overview',
            'dataset_completeness',
        ];

        foreach ($keys as $key) {
            $this->refresh($key);
        }
    }

    /**
     * Compute and store a stat by key.
     *
     * @return array<string, mixed>
     */
    private function computeAndStore(string $key): array
    {
        $value = match ($key) {
            'global' => $this->statsService->getGlobalStats(),
            'category_distribution' => $this->statsService->getCategoryDistribution(),
            'environment_distribution' => $this->statsService->getEnvironmentDistribution(),
            'temporal_distribution' => $this->statsService->getTemporalDistribution(),
            'geo_heatmap' => $this->statsService->getGeoHeatmap(),
            'audio_features' => $this->statsService->getAudioFeatureAverages(),
            'top_locations' => $this->statsService->getTopLocations(),
            'equipment_distribution' => $this->statsService->getEquipmentDistribution(),
            'species_distribution' => $this->statsService->getSpeciesDistribution(),
            'listening_points' => $this->statsService->getListeningPointsOverview(),
            'global_metrics' => $this->statsService->getGlobalMetricsOverview(),
            'environmental_overview' => $this->statsService->getEnvironmentalOverview(),
            'quality_overview' => $this->statsService->getQualityOverview(),
            'dataset_completeness' => $this->statsService->getDatasetCompleteness(),
            default => [],
        };

        $category = match ($key) {
            'global', 'global_metrics' => StatCategory::General,
            'category_distribution', 'environment_distribution', 'temporal_distribution' => StatCategory::Distribution,
            'geo_heatmap', 'top_locations' => StatCategory::Geographic,
            'audio_features' => StatCategory::Audio,
            'species_distribution' => StatCategory::Biodiversity,
            'listening_points' => StatCategory::ListeningPoints,
            'environmental_overview' => StatCategory::Distribution,
            'quality_overview', 'dataset_completeness' => StatCategory::Quality,
            default => StatCategory::General,
        };

        Stat::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'category' => $category,
                'calculated_at' => now(),
            ]
        );

        return $value;
    }

    /**
     * Get the last calculated time for a stat.
     */
    public function lastCalculatedAt(string $key): ?\Carbon\Carbon
    {
        $stat = Stat::key($key)->first();

        return $stat?->calculated_at;
    }

    /**
     * Clear all cached stats.
     */
    public function clear(): void
    {
        Stat::query()->delete();

        if (method_exists(Cache::store(), 'getRedis')) {
            $redis = Cache::store()->getRedis();
            foreach ($redis?->keys('stats:*') ?? [] as $key) {
                Cache::forget($key);
            }
        } else {
            Cache::flush();
        }
    }
}
