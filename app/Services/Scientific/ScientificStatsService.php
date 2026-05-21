<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Enums\MetricType;
use App\Enums\ModerationStatus;
use App\Models\BirdnetDetection;
use App\Models\EnvironmentalObservation;
use App\Models\ListeningPoint;
use App\Models\ScientificMetric;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundLocation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScientificStatsService
{
    /**
     * @param array<string, mixed> $filters
     */
    private function publicSoundQuery(array $filters = []): Builder
    {
        return $this->applyPublicSoundFilters(Sound::public(), $filters);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyPublicSoundFilters(Builder $query, array $filters = []): Builder
    {
        if (! empty($filters['from'])) {
            $query->whereDate('recorded_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('recorded_at', '<=', $filters['to']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['environment_id'])) {
            $query->where('environment_id', (int) $filters['environment_id']);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyPublicSoundTableFilters(QueryBuilder $query, array $filters = []): QueryBuilder
    {
        if (! empty($filters['from'])) {
            $query->whereDate('sounds.recorded_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('sounds.recorded_at', '<=', $filters['to']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('sounds.category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['environment_id'])) {
            $query->where('sounds.environment_id', (int) $filters['environment_id']);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function publicAnalysisQuery(array $filters = []): Builder
    {
        return SoundAnalysis::whereHas('sound', fn (Builder $q) => $this->applyPublicSoundFilters($q->public(), $filters));
    }

    /**
     * @return array<string, mixed>
     */
    public function getGlobalStats(array $filters = []): array
    {
        $query = $this->publicSoundQuery($filters);

        return [
            'total_sounds' => (int) (clone $query)->count(),
            'total_duration_seconds' => (float) ((clone $query)->sum('duration') ?? 0),
            'total_creators' => (int) (clone $query)->distinct('user_id')->count('user_id'),
            'total_analyses' => (int) $this->publicAnalysisQuery($filters)->count(),
            'completed_analyses' => (int) $this->publicAnalysisQuery($filters)->whereNotNull('processed_at')->count(),
            'avg_duration_seconds' => (float) ((clone $query)->avg('duration') ?? 0),
            'total_locations' => (int) SoundLocation::whereHas('sound', fn (Builder $q) => $this->applyPublicSoundFilters($q->public(), $filters))->whereNotNull('public_latitude')->count(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategoryDistribution(array $filters = []): array
    {
        return $this->applyPublicSoundTableFilters(DB::table('sounds'), $filters)
            ->select('categories.name', DB::raw('COUNT(*) as count'), DB::raw('ROUND(AVG(sounds.duration), 2) as avg_duration'))
            ->join('categories', 'sounds.category_id', '=', 'categories.id')
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'count' => (int) $row->count,
                'avg_duration' => (float) $row->avg_duration,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEnvironmentDistribution(array $filters = []): array
    {
        return $this->applyPublicSoundTableFilters(DB::table('sounds'), $filters)
            ->select('environments.name', DB::raw('COUNT(*) as count'))
            ->join('environments', 'sounds.environment_id', '=', 'environments.id')
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->groupBy('environments.id', 'environments.name')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'name' => $row->name,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTemporalDistribution(array $filters = []): array
    {
        return $this->applyPublicSoundTableFilters(DB::table('sounds'), $filters)
            ->select(
                DB::raw("TO_CHAR(recorded_at, 'YYYY-MM') as month"),
                DB::raw('COUNT(*) as count'),
                DB::raw('ROUND(AVG(duration), 2) as avg_duration')
            )
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('recorded_at')
            ->groupBy(DB::raw("TO_CHAR(recorded_at, 'YYYY-MM')"))
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => $row->month,
                'count' => (int) $row->count,
                'avg_duration' => (float) $row->avg_duration,
            ])
            ->values()
            ->all();
    }

    /**
     * Geographic heatmap aggregated into ~0.5 degree cells to preserve privacy.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getGeoHeatmap(array $filters = []): array
    {
        return $this->applyPublicSoundTableFilters(DB::table('sound_locations'), $filters)
            ->select(
                DB::raw('ROUND(public_latitude::numeric, 1) as lat'),
                DB::raw('ROUND(public_longitude::numeric, 1) as lng'),
                DB::raw('COUNT(*) as count'),
                DB::raw("STRING_AGG(DISTINCT categories.name, ', ') as categories")
            )
            ->join('sounds', 'sound_locations.sound_id', '=', 'sounds.id')
            ->leftJoin('categories', 'sounds.category_id', '=', 'categories.id')
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->whereNotNull('sound_locations.public_latitude')
            ->whereNotNull('sound_locations.public_longitude')
            ->groupBy(
                DB::raw('ROUND(public_latitude::numeric, 1)'),
                DB::raw('ROUND(public_longitude::numeric, 1)')
            )
            ->orderByDesc('count')
            ->limit(2000)
            ->get()
            ->map(fn ($row) => [
                'lat' => (float) $row->lat,
                'lng' => (float) $row->lng,
                'count' => (int) $row->count,
                'categories' => $row->categories,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAudioFeatureAverages(array $filters = []): array
    {
        $analyses = $this->publicAnalysisQuery($filters)->get();

        if ($analyses->isEmpty()) {
            return [];
        }

        $featureMap = [
            'zcr' => 'zero_crossing_rate',
            'rms' => 'rms_db',
            'spectral_centroid' => 'spectral_centroid',
            'spectral_rolloff' => 'spectral_rolloff',
            'spectral_bandwidth' => 'spectral_bandwidth',
            'zero_crossing_rate' => 'zero_crossing_rate',
        ];

        $result = [];

        foreach ($featureMap as $feature => $key) {
            $values = $analyses->map(function ($a) use ($key) {
                return $this->audioFeatureValue($a, $key);
            })
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (float) $v);

            if ($values->isNotEmpty()) {
                $result[$feature] = [
                    'mean' => round($values->avg(), 4),
                    'median' => round($this->median($values->all()), 4),
                    'std' => round($this->std($values->all()), 4),
                    'min' => round($values->min(), 4),
                    'max' => round($values->max(), 4),
                    'count' => $values->count(),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAudioFeatureDistribution(array $filters = []): array
    {
        $analyses = $this->publicAnalysisQuery($filters)->get();

        if ($analyses->isEmpty()) {
            return [];
        }

        $featureMap = [
            'zcr' => 'zero_crossing_rate',
            'rms' => 'rms_db',
            'spectral_centroid' => 'spectral_centroid',
            'spectral_rolloff' => 'spectral_rolloff',
        ];

        $result = [];

        foreach ($featureMap as $feature => $key) {
            $allValues = $analyses->map(function ($a) use ($key) {
                return $this->audioFeatureValue($a, $key);
            })
            ->filter(fn ($v) => is_numeric($v))
            ->map(fn ($v) => (float) $v)
            ->sort()
            ->values();

            if ($allValues->count() < 2) {
                continue;
            }

            $bins = 20;
            $min = $allValues->first();
            $max = $allValues->last();

            if ($min === $max) {
                $result[$feature] = [[
                    'range' => sprintf('%.4f', $min),
                    'mid' => $min,
                    'count' => $allValues->count(),
                ]];

                continue;
            }

            $step = ($max - $min) / $bins;

            $histogram = [];
            for ($i = 0; $i < $bins; $i++) {
                $binMin = $min + ($step * $i);
                $binMax = $min + ($step * ($i + 1));
                $histogram[] = [
                    'range' => sprintf('%.4f - %.4f', $binMin, $binMax),
                    'mid' => ($binMin + $binMax) / 2,
                    'count' => $allValues->filter(fn ($v) => $v >= $binMin && ($i === $bins - 1 ? $v <= $binMax : $v < $binMax))->count(),
                ];
            }

            $result[$feature] = $histogram;
        }

        return $result;
    }

    private function audioFeatureValue(SoundAnalysis $analysis, string $key): mixed
    {
        $json = $analysis->features_json ?? [];

        $value = $json[$key] ?? null;

        if ($value === null) {
            $nestedPaths = [
                'rms_db' => [
                    ['temporal', 'rms', 'stats', 'mean'],
                    ['temporal', 'rms_db', 'stats', 'mean'],
                ],
                'zero_crossing_rate' => [
                    ['temporal', 'zero_crossing_rate', 'stats', 'mean'],
                    ['temporal', 'zcr', 'stats', 'mean'],
                ],
                'spectral_centroid' => [
                    ['spectral', 'spectral_centroid', 'stats', 'mean'],
                    ['spectral', 'centroid', 'stats', 'mean'],
                ],
                'spectral_rolloff' => [
                    ['spectral', 'spectral_rolloff', 'stats', 'mean'],
                    ['spectral', 'rolloff', 'stats', 'mean'],
                ],
                'spectral_bandwidth' => [
                    ['spectral', 'spectral_bandwidth', 'stats', 'mean'],
                    ['spectral', 'bandwidth', 'stats', 'mean'],
                ],
            ];

            foreach ($nestedPaths[$key] ?? [] as $path) {
                $value = $this->pluckNested($json, $path);
                if ($value !== null) {
                    break;
                }
            }
        }

        if ($value === null && isset($analysis->{$key})) {
            $value = $analysis->{$key};
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $array
     * @param array<int, string> $path
     */
    private function pluckNested(array $array, array $path): mixed
    {
        $current = $array;
        foreach ($path as $key) {
            if (! is_array($current) || ! array_key_exists($key, $current)) {
                return null;
            }
            $current = $current[$key];
        }
        return $current;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTopLocations(int $limit = 20, array $filters = []): array
    {
        return $this->applyPublicSoundTableFilters(DB::table('sound_locations'), $filters)
            ->select('location_name', DB::raw('COUNT(*) as count'))
            ->join('sounds', 'sound_locations.sound_id', '=', 'sounds.id')
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->whereNotNull('location_name')
            ->where('location_name', '!=', '')
            ->groupBy('location_name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->location_name,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEquipmentDistribution(int $limit = 15, array $filters = []): array
    {
        return $this->applyPublicSoundTableFilters(DB::table('sounds'), $filters)
            ->select('equipment', DB::raw('COUNT(*) as count'))
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('equipment')
            ->where('equipment', '!=', '')
            ->groupBy('equipment')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => $row->equipment,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRawDataSample(int $limit = 100, array $filters = []): array
    {
        return $this->publicSoundQuery($filters)
            ->with(['category', 'environment', 'soundLocation', 'soundAnalysis', 'environmentalObservation'])
            ->latest('recorded_at')
            ->limit($limit)
            ->get()
            ->map(fn (Sound $sound) => [
                'id' => $sound->id,
                'title' => $sound->title,
                'duration' => $sound->duration,
                'category' => $sound->category?->name,
                'environment' => $sound->environment?->name,
                'license' => $sound->license->value ?? null,
                'recorded_at' => $sound->recorded_at?->toIso8601String(),
                'latitude' => $sound->soundLocation?->public_latitude,
                'longitude' => $sound->soundLocation?->public_longitude,
                'location_name' => $sound->soundLocation?->location_name,
                'equipment' => $sound->equipment,
                'play_count' => $sound->play_count,
                'like_count' => $sound->like_count,
                'analysis_status' => $sound->soundAnalysis?->status->value ?? null,
                'environmental_observation' => $sound->environmentalObservation ? [
                    'season' => $sound->environmentalObservation->season?->value,
                    'time_of_day' => $sound->environmentalObservation->time_of_day?->value,
                    'temperature_c' => $sound->environmentalObservation->temperature_c,
                    'humidity_percent' => $sound->environmentalObservation->humidity_percent,
                    'wind_speed_kmh' => $sound->environmentalObservation->wind_speed_kmh,
                    'weather_condition' => $sound->environmentalObservation->weather_condition,
                    'source' => $sound->environmentalObservation->source,
                ] : null,
            ])
            ->all();
    }

    /**
     * Research-grade tabular extract for notebooks and external datasets.
     *
     * @return array<string, mixed>
     */
    public function getResearchDataset(int $limit = 100, int $offset = 0, array $filters = []): array
    {
        $baseQuery = $this->publicSoundQuery($filters);
        $total = (int) (clone $baseQuery)->count();

        $rows = $baseQuery
            ->with([
                'category',
                'environment',
                'soundLocation',
                'soundAnalysis.birdnetDetections',
                'environmentalObservation',
                'scientificMetrics',
            ])
            ->latest('recorded_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (Sound $sound) => $this->researchDatasetRow($sound))
            ->values()
            ->all();

        return [
            'dataset' => [
                'name' => 'arborisis_public_soundscape_research_dataset',
                'schema_version' => 2,
                'row_count' => $total,
                'returned' => count($rows),
                'limit' => $limit,
                'offset' => $offset,
                'next_offset' => ($offset + $limit) < $total ? $offset + $limit : null,
                'license' => 'Public metadata: research and education use with Arborisis citation. Audio file access depends on each sound license.',
                'citation' => 'Arborisis public scientific soundscape dataset, accessed '.now()->toDateString().'.',
                'privacy' => [
                    'scope' => 'public_published_sounds_only',
                    'coordinates' => 'public_obfuscated_coordinates_only',
                    'excluded_fields' => ['user_id', 'exact_latitude', 'exact_longitude', 'private_file_keys', 'personal_data'],
                ],
            ],
            'schema' => $this->getDatasetSchema(),
            'rows' => $rows,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function getDatasetSchema(): array
    {
        return [
            ['name' => 'sound_id', 'type' => 'integer', 'unit' => 'none', 'description' => 'Public internal sound identifier.'],
            ['name' => 'title', 'type' => 'string', 'unit' => 'none', 'description' => 'Public title supplied by the creator.'],
            ['name' => 'recorded_at', 'type' => 'datetime', 'unit' => 'ISO-8601', 'description' => 'Recording timestamp when available.'],
            ['name' => 'duration_seconds', 'type' => 'float', 'unit' => 'seconds', 'description' => 'Public recording duration.'],
            ['name' => 'category', 'type' => 'string|null', 'unit' => 'none', 'description' => 'Arborisis sound category.'],
            ['name' => 'environment', 'type' => 'string|null', 'unit' => 'none', 'description' => 'Habitat or recording environment.'],
            ['name' => 'public_latitude', 'type' => 'float|null', 'unit' => 'decimal degrees', 'description' => 'Obfuscated public latitude only.'],
            ['name' => 'public_longitude', 'type' => 'float|null', 'unit' => 'decimal degrees', 'description' => 'Obfuscated public longitude only.'],
            ['name' => 'equipment', 'type' => 'string|null', 'unit' => 'none', 'description' => 'Recorder and microphone text when provided.'],
            ['name' => 'microphone_position', 'type' => 'string|null', 'unit' => 'none', 'description' => 'Declared microphone placement.'],
            ['name' => 'license', 'type' => 'string|null', 'unit' => 'none', 'description' => 'License attached to the public sound.'],
            ['name' => 'analysis_status', 'type' => 'string|null', 'unit' => 'none', 'description' => 'Audio analysis pipeline status.'],
            ['name' => 'sample_rate_hz', 'type' => 'integer|null', 'unit' => 'Hz', 'description' => 'Detected sample rate.'],
            ['name' => 'loudness_lufs', 'type' => 'float|null', 'unit' => 'LUFS', 'description' => 'Integrated loudness estimate.'],
            ['name' => 'noise_floor_db', 'type' => 'float|null', 'unit' => 'dB', 'description' => 'Estimated noise floor.'],
            ['name' => 'spectral_centroid_hz', 'type' => 'float|null', 'unit' => 'Hz', 'description' => 'Mean spectral centroid.'],
            ['name' => 'spectral_rolloff_hz', 'type' => 'float|null', 'unit' => 'Hz', 'description' => 'Mean spectral rolloff.'],
            ['name' => 'zero_crossing_rate', 'type' => 'float|null', 'unit' => 'ratio', 'description' => 'Mean zero crossing rate.'],
            ['name' => 'birdnet_species_count', 'type' => 'integer', 'unit' => 'count', 'description' => 'Unique species detected above the default confidence threshold.'],
            ['name' => 'top_species', 'type' => 'array', 'unit' => 'none', 'description' => 'Top detected species with confidence summaries.'],
            ['name' => 'biodiversity_score', 'type' => 'float|null', 'unit' => '0-100', 'description' => 'Arborisis descriptive sound biodiversity score.'],
            ['name' => 'acoustic_activity_score', 'type' => 'float|null', 'unit' => '0-100', 'description' => 'Arborisis descriptive acoustic activity score.'],
            ['name' => 'environmental_observation', 'type' => 'object|null', 'unit' => 'mixed', 'description' => 'Weather and temporal context derived from public coordinates.'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function researchDatasetRow(Sound $sound): array
    {
        $analysis = $sound->soundAnalysis;
        $detections = $analysis?->birdnetDetections ?? collect();
        $metricByType = $sound->scientificMetrics->keyBy('metric_type');

        $topSpecies = $detections
            ->where('confidence', '>=', 0.7)
            ->groupBy('scientific_name')
            ->map(fn ($group, string $scientificName) => [
                'scientific_name' => $scientificName,
                'common_name' => $group->first()?->common_name,
                'detections_count' => $group->count(),
                'max_confidence' => round((float) $group->max('confidence'), 4),
            ])
            ->sortByDesc('max_confidence')
            ->take(5)
            ->values()
            ->all();

        return [
            'sound_id' => $sound->id,
            'title' => $sound->title,
            'recorded_at' => $sound->recorded_at?->toIso8601String(),
            'duration_seconds' => $sound->duration !== null ? (float) $sound->duration : null,
            'category' => $sound->category?->name,
            'environment' => $sound->environment?->name,
            'public_latitude' => $sound->soundLocation?->public_latitude !== null ? (float) $sound->soundLocation->public_latitude : null,
            'public_longitude' => $sound->soundLocation?->public_longitude !== null ? (float) $sound->soundLocation->public_longitude : null,
            'location_name' => $sound->soundLocation?->location_name,
            'equipment' => $sound->equipment,
            'microphone_position' => $sound->microphone_position,
            'license' => $sound->license->value ?? null,
            'analysis_status' => $analysis?->status instanceof \BackedEnum ? $analysis->status->value : null,
            'quality_label' => $analysis?->quality_label,
            'sample_rate_hz' => $analysis?->sample_rate,
            'channels' => $analysis?->channels,
            'format' => $analysis?->format,
            'loudness_lufs' => $analysis?->loudness_lufs,
            'peak_db' => $analysis?->peak_db,
            'rms_db' => $analysis?->rms_db,
            'noise_floor_db' => $analysis?->noise_floor_db,
            'spectral_centroid_hz' => $analysis?->spectral_centroid,
            'spectral_rolloff_hz' => $analysis?->spectral_rolloff,
            'zero_crossing_rate' => $analysis?->zero_crossing_rate,
            'acoustic_event_count' => $analysis?->acoustic_event_count,
            'acoustic_diversity_index' => $analysis?->acoustic_diversity_index,
            'birdnet_species_count' => count($topSpecies),
            'top_species' => $topSpecies,
            'biodiversity_score' => isset($metricByType[MetricType::BiodiversityScore->value]) ? (float) $metricByType[MetricType::BiodiversityScore->value]->value : null,
            'acoustic_activity_score' => isset($metricByType[MetricType::AcousticActivityScore->value]) ? (float) $metricByType[MetricType::AcousticActivityScore->value]->value : null,
            'environmental_observation' => $sound->environmentalObservation ? [
                'season' => $sound->environmentalObservation->season?->value,
                'time_of_day' => $sound->environmentalObservation->time_of_day?->value,
                'temperature_c' => $sound->environmentalObservation->temperature_c,
                'humidity_percent' => $sound->environmentalObservation->humidity_percent,
                'wind_speed_kmh' => $sound->environmentalObservation->wind_speed_kmh,
                'weather_condition' => $sound->environmentalObservation->weather_condition,
                'source' => $sound->environmentalObservation->source,
            ] : null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSpeciesDistribution(int $limit = 50, array $filters = []): array
    {
        $query = BirdnetDetection::query()
            ->whereHas('sound', fn (Builder $q) => $this->applyPublicSoundFilters($q->public(), $filters));

        if (isset($filters['min_confidence'])) {
            $query->where('confidence', '>=', (float) $filters['min_confidence']);
        }

        return $query
            ->select(
                'scientific_name',
                'common_name',
                DB::raw('COUNT(*) as detections_count'),
                DB::raw('COUNT(DISTINCT sound_id) as sounds_count'),
                DB::raw('ROUND(AVG(confidence), 4) as mean_confidence'),
                DB::raw('MAX(confidence) as max_confidence')
            )
            ->groupBy('scientific_name', 'common_name')
            ->orderByDesc('sounds_count')
            ->orderByDesc('mean_confidence')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'scientific_name' => $row->scientific_name,
                'common_name' => $row->common_name,
                'detections_count' => (int) $row->detections_count,
                'sounds_count' => (int) $row->sounds_count,
                'mean_confidence' => (float) $row->mean_confidence,
                'max_confidence' => (float) $row->max_confidence,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getQualityOverview(array $filters = []): array
    {
        $analyses = $this->publicAnalysisQuery($filters);
        $total = (int) (clone $analyses)->count();

        $qualityLabels = (clone $analyses)
            ->whereNotNull('quality_label')
            ->select('quality_label', DB::raw('COUNT(*) as count'))
            ->groupBy('quality_label')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->quality_label,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $total),
            ])
            ->values()
            ->all();

        $statusCounts = $this->publicAnalysisQuery($filters)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->map(fn ($row) => [
                'status' => $row->status instanceof \BackedEnum ? $row->status->value : (string) $row->status,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $total),
            ])
            ->values()
            ->all();

        return [
            'analyses_count' => $total,
            'status_counts' => $statusCounts,
            'quality_labels' => $qualityLabels,
            'technical_averages' => [
                'duration_seconds' => round((float) ((clone $analyses)->avg('duration_seconds') ?? 0), 2),
                'sample_rate' => round((float) ((clone $analyses)->avg('sample_rate') ?? 0), 2),
                'loudness_lufs' => round((float) ((clone $analyses)->avg('loudness_lufs') ?? 0), 2),
                'peak_db' => round((float) ((clone $analyses)->avg('peak_db') ?? 0), 2),
                'noise_floor_db' => round((float) ((clone $analyses)->avg('noise_floor_db') ?? 0), 2),
            ],
            'metric_coverage' => [
                'duration_seconds' => $this->analysisMetricCoverage('duration_seconds', $filters, $total),
                'sample_rate' => $this->analysisMetricCoverage('sample_rate', $filters, $total),
                'loudness_lufs' => $this->analysisMetricCoverage('loudness_lufs', $filters, $total),
                'noise_floor_db' => $this->analysisMetricCoverage('noise_floor_db', $filters, $total),
                'spectral_centroid' => $this->analysisMetricCoverage('spectral_centroid', $filters, $total),
                'zero_crossing_rate' => $this->analysisMetricCoverage('zero_crossing_rate', $filters, $total),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getDatasetCompleteness(array $filters = []): array
    {
        $query = $this->publicSoundQuery($filters);
        $total = (int) (clone $query)->count();

        $fields = [
            'recorded_at' => (clone $query)->whereNotNull('recorded_at')->count(),
            'duration' => (clone $query)->whereNotNull('duration')->where('duration', '>', 0)->count(),
            'category' => (clone $query)->whereNotNull('category_id')->count(),
            'environment' => (clone $query)->whereNotNull('environment_id')->count(),
            'public_location' => (clone $query)->whereHas('soundLocation', fn (Builder $q) => $q->whereNotNull('public_latitude')->whereNotNull('public_longitude'))->count(),
            'equipment' => (clone $query)->whereNotNull('equipment')->where('equipment', '!=', '')->count(),
            'environmental_observation' => (clone $query)->whereHas('environmentalObservation')->count(),
            'open_meteo_weather' => (clone $query)->whereHas('environmentalObservation', fn (Builder $q) => $q->where('source', 'like', '%open-meteo%'))->count(),
            'license' => (clone $query)->whereNotNull('license')->count(),
            'completed_analysis' => (clone $query)->whereHas('soundAnalysis', fn (Builder $q) => $q->whereNotNull('processed_at'))->count(),
            'species_detection' => (clone $query)->whereHas('soundAnalysis.birdnetDetections')->count(),
        ];

        return [
            'sounds_count' => $total,
            'fields' => collect($fields)
                ->map(fn (int $count, string $field) => [
                    'field' => $field,
                    'count' => $count,
                    'missing' => max(0, $total - $count),
                    'percentage' => $this->percentage($count, $total),
                ])
                ->values()
                ->all(),
            'scientific_readiness_score' => $this->scientificReadinessScore($fields, $total),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function analysisMetricCoverage(string $column, array $filters, int $total): array
    {
        $count = (int) $this->publicAnalysisQuery($filters)->whereNotNull($column)->count();

        return [
            'count' => $count,
            'percentage' => $this->percentage($count, $total),
        ];
    }

    private function percentage(int $count, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 2);
    }

    /**
     * @param array<string, int> $fields
     */
    private function scientificReadinessScore(array $fields, int $total): float
    {
        if ($total === 0 || $fields === []) {
            return 0.0;
        }

        $weights = [
            'recorded_at' => 1.1,
            'duration' => 1.0,
            'category' => 1.0,
            'environment' => 1.0,
            'public_location' => 1.2,
            'equipment' => 0.7,
            'environmental_observation' => 0.9,
            'open_meteo_weather' => 0.6,
            'license' => 0.5,
            'completed_analysis' => 1.4,
            'species_detection' => 1.1,
        ];

        $score = 0.0;
        $totalWeight = 0.0;

        foreach ($fields as $field => $count) {
            $weight = $weights[$field] ?? 1.0;
            $score += ($count / $total) * $weight;
            $totalWeight += $weight;
        }

        return round(($score / $totalWeight) * 100, 2);
    }

    /**
     * @param array<int, float> $values
     */
    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = (int) floor($count / 2);

        if ($count % 2) {
            return $values[$middle];
        }

        return ($values[$middle - 1] + $values[$middle]) / 2;
    }

    /**
     * @param array<int, float> $values
     */
    private function std(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn (float $v): float => pow($v - $mean, 2), $values)) / $count;

        return sqrt($variance);
    }

    /**
     * @return array<string, mixed>
     */
    public function getListeningPointsOverview(): array
    {
        $total = ListeningPoint::approved()->count();
        $withRecordings = ListeningPoint::approved()->where('recordings_count', '>', 0)->count();

        $byHabitat = ListeningPoint::approved()
            ->select('habitat_type', DB::raw('COUNT(*) as count'))
            ->whereNotNull('habitat_type')
            ->groupBy('habitat_type')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'habitat' => $row->habitat_type,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $mostActive = ListeningPoint::approved()
            ->where('recordings_count', '>', 0)
            ->orderByDesc('recordings_count')
            ->limit(10)
            ->get()
            ->map(fn ($point) => [
                'slug' => $point->slug,
                'title' => $point->title,
                'recordings_count' => $point->recordings_count,
                'species_count' => $point->species_detected_count,
            ])
            ->values()
            ->all();

        return [
            'total_points' => $total,
            'points_with_recordings' => $withRecordings,
            'by_habitat' => $byHabitat,
            'most_active' => $mostActive,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getGlobalMetricsOverview(array $filters = []): array
    {
        $sbsValues = $this->publicMetricValues(MetricType::BiodiversityScore, $filters);
        $aasValues = $this->publicMetricValues(MetricType::AcousticActivityScore, $filters);

        return [
            'biodiversity_score' => $this->metricSummary($sbsValues),
            'acoustic_activity_score' => $this->metricSummary($aasValues),
            'weather_context' => $this->getModelWeatherContext($filters),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return Collection<int, float>
     */
    private function publicMetricValues(MetricType $type, array $filters = []): Collection
    {
        return $this->applyPublicSoundTableFilters(
            DB::table('scientific_metrics')->join('sounds', function ($join) {
                $join->on('scientific_metrics.measurable_id', '=', 'sounds.id')
                    ->where('scientific_metrics.measurable_type', Sound::class);
            }),
            $filters
        )
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->where('scientific_metrics.metric_type', $type->value)
            ->whereNotNull('scientific_metrics.value')
            ->pluck('scientific_metrics.value')
            ->map(fn ($value): float => (float) $value)
            ->values();
    }

    /**
     * @param Collection<int, float> $values
     *
     * @return array<string, mixed>
     */
    private function metricSummary(Collection $values): array
    {
        return [
            'count' => $values->count(),
            'mean' => $values->isNotEmpty() ? round($values->avg(), 2) : null,
            'median' => $values->isNotEmpty() ? round($this->median($values->all()), 2) : null,
            'min' => $values->isNotEmpty() ? round($values->min(), 2) : null,
            'max' => $values->isNotEmpty() ? round($values->max(), 2) : null,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getModelWeatherContext(array $filters = []): array
    {
        $environmental = $this->getEnvironmentalOverview($filters);

        return [
            'coverage' => $environmental['coverage'],
            'averages' => $environmental['averages'],
            'weather_conditions' => $environmental['weather_conditions'],
            'by_season' => $environmental['by_season'],
            'by_time_of_day' => $environmental['by_time_of_day'],
            'activity_by_weather_condition' => $environmental['activity_by_weather_condition'],
            'biodiversity_by_weather_condition' => $this->metricByWeatherCondition(MetricType::BiodiversityScore, $filters),
            'individual_sounds' => $this->getIndividualSoundWeatherRows($filters),
            'listening_points' => $this->getListeningPointWeatherOverview($filters),
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function getIndividualSoundWeatherRows(array $filters = [], int $limit = 100): array
    {
        return $this->publicSoundQuery($filters)
            ->with(['category', 'environment', 'soundLocation', 'environmentalObservation'])
            ->whereHas('soundLocation', fn (Builder $q) => $q
                ->whereNotNull('public_latitude')
                ->whereNotNull('public_longitude'))
            ->latest('recorded_at')
            ->limit($limit)
            ->get()
            ->map(fn (Sound $sound) => [
                'sound_id' => $sound->id,
                'title' => $sound->title,
                'recorded_at' => $sound->recorded_at?->toIso8601String(),
                'duration_seconds' => $sound->duration !== null ? (float) $sound->duration : null,
                'category' => $sound->category?->name,
                'environment' => $sound->environment?->name,
                'public_latitude' => $sound->soundLocation?->public_latitude !== null ? (float) $sound->soundLocation->public_latitude : null,
                'public_longitude' => $sound->soundLocation?->public_longitude !== null ? (float) $sound->soundLocation->public_longitude : null,
                'location_name' => $sound->soundLocation?->location_name,
                'weather' => $sound->environmentalObservation ? [
                    'season' => $sound->environmentalObservation->season?->value,
                    'time_of_day' => $sound->environmentalObservation->time_of_day?->value,
                    'temperature_c' => $sound->environmentalObservation->temperature_c !== null ? (float) $sound->environmentalObservation->temperature_c : null,
                    'humidity_percent' => $sound->environmentalObservation->humidity_percent,
                    'wind_speed_kmh' => $sound->environmentalObservation->wind_speed_kmh !== null ? (float) $sound->environmentalObservation->wind_speed_kmh : null,
                    'wind_direction' => $sound->environmentalObservation->wind_direction,
                    'is_raining' => $sound->environmentalObservation->is_raining,
                    'is_snowing' => $sound->environmentalObservation->is_snowing,
                    'weather_condition' => $sound->environmentalObservation->weather_condition,
                    'source' => $sound->environmentalObservation->source,
                ] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function getListeningPointWeatherOverview(array $filters = []): array
    {
        $pointQuery = ListeningPoint::approved()
            ->whereNotNull('public_latitude')
            ->whereNotNull('public_longitude');

        if (! empty($filters['environment_id'])) {
            $pointQuery->where('environment_id', (int) $filters['environment_id']);
        }

        $totalPoints = (int) (clone $pointQuery)->count();

        $observationQuery = EnvironmentalObservation::query()
            ->whereNull('sound_id')
            ->whereNotNull('listening_point_id')
            ->whereHas('listeningPoint', function (Builder $q) use ($filters): void {
                $q->approved()
                    ->whereNotNull('public_latitude')
                    ->whereNotNull('public_longitude');

                if (! empty($filters['environment_id'])) {
                    $q->where('environment_id', (int) $filters['environment_id']);
                }
            });

        if (! empty($filters['from'])) {
            $observationQuery->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $observationQuery->whereDate('created_at', '<=', $filters['to']);
        }

        $totalObservations = (int) (clone $observationQuery)->count();

        $conditions = (clone $observationQuery)
            ->select('weather_condition', DB::raw('COUNT(*) as count'))
            ->whereNotNull('weather_condition')
            ->groupBy('weather_condition')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'condition' => $row->weather_condition,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $totalObservations),
            ])
            ->values()
            ->all();

        $averages = (clone $observationQuery)
            ->selectRaw('
                ROUND(AVG(temperature_c), 2) as temperature_c,
                ROUND(AVG(humidity_percent), 2) as humidity_percent,
                ROUND(AVG(wind_speed_kmh), 2) as wind_speed_kmh
            ')
            ->first();

        return [
            'points_count' => $totalPoints,
            'observations_count' => $totalObservations,
            'coverage' => [
                'points_count' => $totalPoints,
                'with_environmental_observation' => $totalObservations,
                'percentage' => $this->percentage($totalObservations, $totalPoints),
            ],
            'averages' => [
                'temperature_c' => $averages?->temperature_c !== null ? (float) $averages->temperature_c : null,
                'humidity_percent' => $averages?->humidity_percent !== null ? (float) $averages->humidity_percent : null,
                'wind_speed_kmh' => $averages?->wind_speed_kmh !== null ? (float) $averages->wind_speed_kmh : null,
            ],
            'weather_conditions' => $conditions,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<int, array<string, mixed>>
     */
    private function metricByWeatherCondition(MetricType $type, array $filters = []): array
    {
        $query = DB::table('environmental_observations')
            ->join('sounds', 'environmental_observations.sound_id', '=', 'sounds.id')
            ->join('scientific_metrics', function ($join) use ($type) {
                $join->on('scientific_metrics.measurable_id', '=', 'sounds.id')
                    ->where('scientific_metrics.measurable_type', Sound::class)
                    ->where('scientific_metrics.metric_type', $type->value);
            });

        return $this->applyPublicSoundTableFilters($query, $filters)
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->whereNotNull('environmental_observations.weather_condition')
            ->select(
                'environmental_observations.weather_condition',
                DB::raw('COUNT(*) as sample_size'),
                DB::raw('ROUND(AVG(scientific_metrics.value), 2) as mean_value')
            )
            ->groupBy('environmental_observations.weather_condition')
            ->orderByDesc('sample_size')
            ->get()
            ->map(fn ($row) => [
                'condition' => $row->weather_condition,
                'sample_size' => (int) $row->sample_size,
                'mean_value' => (float) $row->mean_value,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getEnvironmentalOverview(array $filters = []): array
    {
        $soundQuery = $this->publicSoundQuery($filters);
        $totalSounds = (int) (clone $soundQuery)->count();

        $observationQuery = EnvironmentalObservation::query()
            ->whereHas('sound', fn (Builder $q) => $this->applyPublicSoundFilters($q->public(), $filters));

        $totalObservations = (int) (clone $observationQuery)->count();

        $sources = (clone $observationQuery)
            ->select('source', DB::raw('COUNT(*) as count'))
            ->groupBy('source')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $totalObservations),
            ])
            ->values()
            ->all();

        $conditions = (clone $observationQuery)
            ->select('weather_condition', DB::raw('COUNT(*) as count'))
            ->whereNotNull('weather_condition')
            ->groupBy('weather_condition')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => [
                'condition' => $row->weather_condition,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $totalObservations),
            ])
            ->values()
            ->all();

        $bySeason = (clone $observationQuery)
            ->select('season', DB::raw('COUNT(*) as count'))
            ->whereNotNull('season')
            ->groupBy('season')
            ->orderBy('season')
            ->get()
            ->map(fn ($row) => [
                'season' => $row->season instanceof \BackedEnum ? $row->season->value : (string) $row->season,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $totalObservations),
            ])
            ->values()
            ->all();

        $byTimeOfDay = (clone $observationQuery)
            ->select('time_of_day', DB::raw('COUNT(*) as count'))
            ->whereNotNull('time_of_day')
            ->groupBy('time_of_day')
            ->orderBy('time_of_day')
            ->get()
            ->map(fn ($row) => [
                'time_of_day' => $row->time_of_day instanceof \BackedEnum ? $row->time_of_day->value : (string) $row->time_of_day,
                'count' => (int) $row->count,
                'percentage' => $this->percentage((int) $row->count, $totalObservations),
            ])
            ->values()
            ->all();

        $averages = (clone $observationQuery)
            ->selectRaw('
                ROUND(AVG(temperature_c), 2) as temperature_c,
                ROUND(AVG(humidity_percent), 2) as humidity_percent,
                ROUND(AVG(wind_speed_kmh), 2) as wind_speed_kmh
            ')
            ->first();

        $activityByCondition = DB::table('environmental_observations')
            ->join('sounds', 'environmental_observations.sound_id', '=', 'sounds.id')
            ->join('scientific_metrics', function ($join) {
                $join->on('scientific_metrics.measurable_id', '=', 'sounds.id')
                    ->where('scientific_metrics.measurable_type', Sound::class)
                    ->where('scientific_metrics.metric_type', MetricType::AcousticActivityScore->value);
            });

        $activityByCondition = $this->applyPublicSoundTableFilters($activityByCondition, $filters)
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->whereNotNull('environmental_observations.weather_condition')
            ->select(
                'environmental_observations.weather_condition',
                DB::raw('COUNT(*) as sample_size'),
                DB::raw('ROUND(AVG(scientific_metrics.value), 2) as mean_acoustic_activity_score')
            )
            ->groupBy('environmental_observations.weather_condition')
            ->orderByDesc('sample_size')
            ->get()
            ->map(fn ($row) => [
                'condition' => $row->weather_condition,
                'sample_size' => (int) $row->sample_size,
                'mean_acoustic_activity_score' => (float) $row->mean_acoustic_activity_score,
            ])
            ->values()
            ->all();

        return [
            'observations_count' => $totalObservations,
            'coverage' => [
                'sounds_count' => $totalSounds,
                'with_environmental_observation' => $totalObservations,
                'percentage' => $this->percentage($totalObservations, $totalSounds),
            ],
            'sources' => $sources,
            'weather_conditions' => $conditions,
            'by_season' => $bySeason,
            'by_time_of_day' => $byTimeOfDay,
            'averages' => [
                'temperature_c' => $averages?->temperature_c !== null ? (float) $averages->temperature_c : null,
                'humidity_percent' => $averages?->humidity_percent !== null ? (float) $averages->humidity_percent : null,
                'wind_speed_kmh' => $averages?->wind_speed_kmh !== null ? (float) $averages->wind_speed_kmh : null,
            ],
            'activity_by_weather_condition' => $activityByCondition,
        ];
    }
}
