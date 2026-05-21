<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Scientific\IndexListeningPointsRequest;
use App\Models\BirdnetDetection;
use App\Models\ListeningPoint;
use App\Services\OpenSearch\SoundSearchRepository;
use App\Services\Scientific\GeoMatchingService;
use App\Services\Scientific\OpenMeteoEnvironmentalDataService;
use App\Services\Scientific\ScientificStatsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ListeningPointController extends Controller
{
    public function __construct(
        private readonly SoundSearchRepository $searchRepository,
        private readonly GeoMatchingService $geoMatching,
        private readonly OpenMeteoEnvironmentalDataService $weatherService,
        private readonly ScientificStatsService $statsService,
    ) {}

    public function index(IndexListeningPointsRequest $request): Response
    {
        $filters = $request->filters();
        $publicPoints = ListeningPoint::query()
            ->publiclyVisible();

        $speciesCountSubquery = BirdnetDetection::query()
            ->selectRaw('COUNT(DISTINCT birdnet_detections.scientific_name)')
            ->join('sounds', 'birdnet_detections.sound_id', '=', 'sounds.id')
            ->whereColumn('sounds.listening_point_id', 'listening_points.id')
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public');

        $pointsQuery = (clone $publicPoints)
            ->select('listening_points.*')
            ->selectSub($speciesCountSubquery, 'detected_species_count')
            ->with(['creator', 'environment', 'contributors'])
            ->withCount(['contributors', 'versions'])
            ->when($filters['habitat'] !== '', fn (Builder $query) => $query->where('habitat_type', $filters['habitat']))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $search = '%'.mb_strtolower($filters['q']).'%';

                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereRaw('LOWER(title) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(COALESCE(description, \'\')) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(COALESCE(admin_level_1, \'\')) LIKE ?', [$search])
                        ->orWhereRaw('LOWER(COALESCE(country_code, \'\')) LIKE ?', [$search]);
                });
            });

        match ($filters['sort']) {
            'active' => $pointsQuery->orderByDesc('recordings_count')->orderByDesc('last_recorded_at'),
            'species' => $pointsQuery->orderByDesc('detected_species_count')->orderByDesc('last_recorded_at'),
            'oldest' => $pointsQuery->orderBy('first_recorded_at')->orderBy('title'),
            'alpha' => $pointsQuery->orderBy('title'),
            default => $pointsQuery->latest('last_recorded_at')->orderByDesc('recordings_count'),
        };

        $points = $pointsQuery
            ->paginate(24)
            ->withQueryString()
            ->through(fn (ListeningPoint $point) => [
                'id' => $point->id,
                'slug' => $point->slug,
                'title' => $point->title,
                'description' => $point->description,
                'public_latitude' => (float) $point->public_latitude,
                'public_longitude' => (float) $point->public_longitude,
                'public_accuracy_meters' => $point->public_accuracy_meters,
                'habitat_type' => $point->habitat_type,
                'country_code' => $point->country_code,
                'admin_level_1' => $point->admin_level_1,
                'recordings_count' => $point->recordings_count,
                'species_detected_count' => (int) ($point->detected_species_count ?? $point->species_detected_count),
                'first_recorded_at' => $point->first_recorded_at?->toIso8601String(),
                'last_recorded_at' => $point->last_recorded_at?->toIso8601String(),
                'dominant_tags' => array_slice($point->dominant_tags ?? [], 0, 4),
                'versions_count' => $point->versions_count,
                'contributors_count' => $point->contributors_count,
                'contributors' => $point->contributors
                    ->take(3)
                    ->map(fn ($contributor) => [
                        'id' => $contributor->id,
                        'name' => $contributor->name,
                        'slug' => $contributor->slug,
                    ])
                    ->values(),
                'creator' => [
                    'name' => $point->creator->name,
                    'slug' => $point->creator->slug,
                ],
                'environment' => $point->environment?->name,
                'last_cover_url' => $point->sounds()
                    ->public()
                    ->whereNotNull('cover_image')
                    ->latest('recorded_at')
                    ->value('cover_image')
                    ? (fn ($cover) => \Illuminate\Support\Facades\Storage::disk('public')->url($cover))($point->sounds()->public()->whereNotNull('cover_image')->latest('recorded_at')->value('cover_image'))
                    : null,
            ]);

        $catalog = (clone $publicPoints)
            ->selectRaw('COUNT(*) as total_points')
            ->selectRaw('COALESCE(SUM(recordings_count), 0) as total_recordings')
            ->first();

        $totalSpeciesSignals = (int) BirdnetDetection::query()
            ->join('sounds', 'birdnet_detections.sound_id', '=', 'sounds.id')
            ->join('listening_points', 'sounds.listening_point_id', '=', 'listening_points.id')
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->whereNull('sounds.deleted_at')
            ->whereNull('listening_points.deleted_at')
            ->where('listening_points.moderation_status', 'approved')
            ->distinct('birdnet_detections.scientific_name')
            ->count('birdnet_detections.scientific_name');

        $habitats = (clone $publicPoints)
            ->select('habitat_type', DB::raw('COUNT(*) as points_count'), DB::raw('COALESCE(SUM(recordings_count), 0) as recordings_count'))
            ->whereNotNull('habitat_type')
            ->groupBy('habitat_type')
            ->orderByDesc('points_count')
            ->get()
            ->map(fn ($row) => [
                'value' => $row->habitat_type,
                'points_count' => (int) $row->points_count,
                'recordings_count' => (int) $row->recordings_count,
            ]);

        return Inertia::render('ListeningPoints/Index', [
            'points' => $points,
            'filters' => $filters,
            'catalog' => [
                'total_points' => (int) ($catalog->total_points ?? 0),
                'total_recordings' => (int) ($catalog->total_recordings ?? 0),
                'total_species_signals' => $totalSpeciesSignals,
                'habitats' => $habitats,
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $point = ListeningPoint::approved()
            ->with([
                'creator',
                'environment',
                'sounds' => fn ($q) => $q->public()->with(['user', 'tags', 'soundFile', 'soundAnalysis.birdnetDetections', 'scientificMetrics'])->latest('recorded_at'),
                'contributors',
                'scientificMetrics',
                'versions.actor',
            ])
            ->where('slug', $slug)
            ->firstOrFail();

        $sounds = $point->sounds;
        $currentWeather = $this->weatherService->currentForListeningPoint($point);

        // Timeline data
        $timeline = $sounds->map(fn ($sound) => [
            'id' => $sound->id,
            'slug' => $sound->slug,
            'title' => $sound->title,
            'recorded_at' => $sound->recorded_at?->toIso8601String(),
            'duration' => $sound->duration,
            'cover_url' => $sound->cover_url,
            'user' => [
                'name' => $sound->user->name,
                'slug' => $sound->user->slug,
            ],
            'species_count' => $sound->soundAnalysis?->birdnetDetections->count() ?? 0,
            'biodiversity_score' => $sound->scientificMetrics->firstWhere('metric_type', 'biodiversity_score')?->value ?? null,
        ]);

        // Species aggregated
        $speciesList = [];
        foreach ($sounds as $sound) {
            foreach ($sound->soundAnalysis?->birdnetDetections ?? [] as $detection) {
                $key = $detection->scientific_name;
                if (! isset($speciesList[$key])) {
                    $speciesList[$key] = [
                        'scientific_name' => $detection->scientific_name,
                        'common_name' => $detection->common_name,
                        'count' => 0,
                        'avg_confidence' => 0,
                        'detections' => [],
                    ];
                }
                $speciesList[$key]['count']++;
                $speciesList[$key]['detections'][] = [
                    'sound_id' => $sound->id,
                    'confidence' => $detection->confidence,
                    'recorded_at' => $sound->recorded_at?->toIso8601String(),
                ];
            }
        }

        foreach ($speciesList as &$s) {
            $confidences = array_column($s['detections'], 'confidence');
            $s['avg_confidence'] = count($confidences) > 0 ? round(array_sum($confidences) / count($confidences), 3) : 0;
        }
        unset($s);

        usort($speciesList, fn ($a, $b) => $b['count'] <=> $a['count']);

        // Metrics
        $metrics = $point->scientificMetrics->keyBy('metric_type');

        // Nearby points
        $nearby = $this->geoMatching->findNearbyPoints(
            (float) $point->public_latitude,
            (float) $point->public_longitude,
            5000,
            5,
        )->reject(fn ($p) => $p->id === $point->id)->values();

        return Inertia::render('ListeningPoints/Show', [
            'point' => [
                'id' => $point->id,
                'slug' => $point->slug,
                'title' => $point->title,
                'description' => $point->description,
                'public_latitude' => (float) $point->public_latitude,
                'public_longitude' => (float) $point->public_longitude,
                'public_accuracy_meters' => $point->public_accuracy_meters,
                'habitat_type' => $point->habitat_type,
                'recordings_count' => $point->recordings_count,
                'species_detected_count' => count($speciesList),
                'first_recorded_at' => $point->first_recorded_at?->toIso8601String(),
                'last_recorded_at' => $point->last_recorded_at?->toIso8601String(),
                'dominant_tags' => $point->dominant_tags ?? [],
                'creator' => [
                    'name' => $point->creator->name,
                    'slug' => $point->creator->slug,
                ],
                'environment' => $point->environment?->name,
            ],
            'timeline' => $timeline,
            'species' => array_values($speciesList),
            'metrics' => [
                'biodiversity_score' => $metrics->get('biodiversity_score')?->value ?? null,
                'acoustic_activity_score' => $metrics->get('acoustic_activity_score')?->value ?? null,
                'shannon_index' => $metrics->get('shannon_index')?->value ?? null,
                'simpson_index' => $metrics->get('simpson_index')?->value ?? null,
                'acoustic_complexity_index' => $metrics->get('acoustic_complexity_index')?->value ?? null,
                'temporal_turnover' => $metrics->get('temporal_turnover')?->value ?? null,
                'acoustic_consistency_score' => $metrics->get('acoustic_consistency_score')?->value ?? null,
                'species_richness' => $metrics->get('species_richness')?->value ?? null,
            ],
            'scientificMetrics' => $point->scientificMetrics->map(fn ($m) => [
                'metric_type' => $m->metric_type,
                'value' => (float) $m->value,
                'components' => $m->components,
                'computed_at' => $m->computed_at?->toIso8601String(),
            ])->values(),
            'currentWeather' => $currentWeather ? [
                'temperature_c' => $currentWeather->temperature_c === null ? null : (float) $currentWeather->temperature_c,
                'humidity_percent' => $currentWeather->humidity_percent,
                'wind_speed_kmh' => $currentWeather->wind_speed_kmh === null ? null : (float) $currentWeather->wind_speed_kmh,
                'wind_direction' => $currentWeather->wind_direction,
                'is_raining' => $currentWeather->is_raining,
                'is_snowing' => $currentWeather->is_snowing,
                'weather_condition' => $currentWeather->weather_condition,
                'observed_at' => ($currentWeather->raw_data ?? [])['observed_at'] ?? null,
                'fetched_at' => ($currentWeather->raw_data ?? [])['fetched_at'] ?? $currentWeather->updated_at?->toIso8601String(),
                'source' => $currentWeather->source,
            ] : null,
            'versions' => $point->versions->take(12)->map(fn ($version) => [
                'version_number' => $version->version_number,
                'version_hash' => $version->version_hash,
                'parent_version_hash' => $version->parent_version_hash,
                'event' => $version->event->value,
                'event_label' => $version->event->label(),
                'summary' => $version->summary,
                'captured_at' => $version->captured_at?->toIso8601String(),
                'actor' => $version->actor ? [
                    'name' => $version->actor->name,
                    'slug' => $version->actor->slug,
                ] : null,
                'changed_fields' => array_keys($version->diff ?? []),
                'short_hash' => substr($version->version_hash, 0, 10),
                'short_parent_hash' => $version->parent_version_hash ? substr($version->parent_version_hash, 0, 10) : null,
            ])->values(),
            'nearbyPoints' => $nearby->map(fn ($p) => [
                'slug' => $p->slug,
                'title' => $p->title,
                'public_latitude' => (float) $p->public_latitude,
                'public_longitude' => (float) $p->public_longitude,
                'distance_meters' => (float) ($p->distance_meters ?? 0),
            ])->values(),
        ]);
    }
}
