<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Events\Gamification\SoundWalkSubmitted;
use App\Models\ArborisisPoint;
use App\Models\SoundWalk;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SoundWalkService
{
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function previewItinerary(array $data): array
    {
        $waypoints = $this->resolveWaypointCoordinates($data);
        $route = $this->buildRoute($waypoints);

        return [
            'waypoints' => array_map(fn (array $waypoint): array => [
                'title' => $waypoint['title'] ?? null,
                'place_query' => $waypoint['place_query'] ?? null,
                'lat' => round((float) ($waypoint['lat'] ?? $waypoint['latitude'] ?? 0), 8),
                'lng' => round((float) ($waypoint['lng'] ?? $waypoint['longitude'] ?? 0), 8),
                'order' => (int) ($waypoint['order'] ?? 0),
                'geocoded_place_name' => $waypoint['geocoded_place_name'] ?? null,
                'geocoded_source' => $waypoint['geocoded_source'] ?? null,
            ], $waypoints),
            'route' => [
                'source' => $route['source'] ?? null,
                'profile' => $route['profile'] ?? null,
                'distance_meters' => $route['distance_meters'] ?? null,
                'duration_seconds' => $route['duration_seconds'] ?? null,
                'coordinates_count' => isset($route['coordinates']) && is_array($route['coordinates']) ? count($route['coordinates']) : 0,
            ],
        ];
    }

    public function createItinerary(User $user, array $data): SoundWalk
    {
        return DB::transaction(function () use ($user, $data) {
            $data['waypoints'] = $this->resolveWaypointCoordinates($data);

            $sensitivity = NatureSensitivityLevel::tryFrom($data['nature_sensitivity_level'] ?? 'normal')
                ?? NatureSensitivityLevel::Normal;

            $startLat = (float) ($data['start_latitude'] ?? ($data['waypoints'][0]['lat'] ?? 0));
            $startLng = (float) ($data['start_longitude'] ?? ($data['waypoints'][0]['lng'] ?? 0));

            $coordinates = SoundWalk::publicCoordinates($startLat, $startLng, $sensitivity);

            $route = $this->buildRoute($data['waypoints']);

            $soundWalk = SoundWalk::create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'slug' => $this->generateSlug($data['title']),
                'description' => $data['description'] ?? null,
                'visibility_status' => 'public',
                'moderation_status' => ModerationStatus::Pending,
                'route_geometry' => $route,
                'start_latitude' => $startLat,
                'start_longitude' => $startLng,
                'approximate_start_latitude' => $coordinates['approximate_latitude'],
                'approximate_start_longitude' => $coordinates['approximate_longitude'],
                'estimated_duration_minutes' => $data['estimated_duration_minutes'] ?? null,
                'difficulty_level' => $data['difficulty_level'] ?? 1,
                'tags' => $data['tags'] ?? [],
                'audio_environment_type' => $data['audio_environment_type'] ?? null,
                'cover_image' => $data['cover_image'] ?? null,
            ]);

            if (! empty($data['waypoints'])) {
                foreach ($data['waypoints'] as $index => $wp) {
                    $soundWalk->points()->create([
                        'arborisis_point_id' => $wp['arborisis_point_id'] ?? null,
                        'title' => $wp['title'] ?? null,
                        'description' => $wp['description'] ?? null,
                        'latitude' => (float) ($wp['lat'] ?? $wp['latitude'] ?? 0),
                        'longitude' => (float) ($wp['lng'] ?? $wp['longitude'] ?? 0),
                        'order' => (int) ($wp['order'] ?? $index),
                        'stop_metadata' => [
                            'recording_tips' => $wp['recording_tips'] ?? null,
                            'recommended_time' => $wp['recommended_time'] ?? null,
                        ],
                    ]);
                }
            }

            SoundWalkSubmitted::dispatch($soundWalk);

            return $soundWalk;
        });
    }

    public function updateItinerary(SoundWalk $soundWalk, array $data): SoundWalk
    {
        return DB::transaction(function () use ($soundWalk, $data) {
            if (isset($data['title']) && $data['title'] !== $soundWalk->title) {
                $data['slug'] = $this->generateSlug($data['title']);
            }

            if (isset($data['start_latitude'], $data['start_longitude']) || isset($data['nature_sensitivity_level'])) {
                $coordinates = SoundWalk::publicCoordinates(
                    (float) ($data['start_latitude'] ?? $soundWalk->start_latitude),
                    (float) ($data['start_longitude'] ?? $soundWalk->start_longitude),
                    $data['nature_sensitivity_level'] ?? $soundWalk->nature_sensitivity_level ?? NatureSensitivityLevel::Normal,
                );

                $data['approximate_start_latitude'] = $coordinates['approximate_latitude'];
                $data['approximate_start_longitude'] = $coordinates['approximate_longitude'];
            }

            $soundWalk->update($data);

            return $soundWalk->fresh();
        });
    }

    private function generateSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $count = 1;

        while (SoundWalk::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * @param array<int, array<string, mixed>> $waypoints
     * @return array<string, mixed>
     */
    private function buildRoute(array $waypoints): array
    {
        $ordered = collect($waypoints)
            ->sortBy(fn (array $waypoint): int => (int) ($waypoint['order'] ?? 0))
            ->values()
            ->all();

        $fallback = [
            'type' => 'LineString',
            'source' => 'waypoints',
            'coordinates' => array_map(
                fn (array $waypoint): array => [
                    round((float) ($waypoint['lng'] ?? $waypoint['longitude'] ?? 0), 8),
                    round((float) ($waypoint['lat'] ?? $waypoint['latitude'] ?? 0), 8),
                ],
                $ordered,
            ),
            'waypoints' => array_map(fn (array $waypoint): array => [
                'lat' => round((float) ($waypoint['lat'] ?? $waypoint['latitude'] ?? 0), 8),
                'lng' => round((float) ($waypoint['lng'] ?? $waypoint['longitude'] ?? 0), 8),
                'order' => (int) ($waypoint['order'] ?? 0),
                'title' => $waypoint['title'] ?? null,
                'geocoded_place_name' => $waypoint['geocoded_place_name'] ?? null,
            ], $ordered),
        ];

        $routed = $this->routeWaypoints($ordered);

        return $routed ?? $fallback;
    }

    /**
     * @param array<int, array<string, mixed>> $waypoints
     * @return array<string, mixed>|null
     */
    private function routeWaypoints(array $waypoints): ?array
    {
        if (! (bool) config('services.osrm.enabled', true) || count($waypoints) < 2) {
            return null;
        }

        $coordinates = collect($waypoints)
            ->map(fn (array $waypoint): string => round((float) ($waypoint['lng'] ?? $waypoint['longitude'] ?? 0), 8).','.round((float) ($waypoint['lat'] ?? $waypoint['latitude'] ?? 0), 8))
            ->implode(';');

        try {
            $baseUrl = rtrim((string) config('services.osrm.base_url', 'https://routing.openstreetmap.de/routed-foot'), '/');
            $profile = trim((string) config('services.osrm.profile', 'foot'), '/');
            $url = "{$baseUrl}/route/v1/{$profile}/{$coordinates}";

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => (string) config('services.osrm.user_agent', config('app.name', 'Arborisis').'/1.0'),
            ])
                ->timeout((int) config('services.osrm.timeout', 10))
                ->get($url, [
                    'overview' => 'full',
                    'geometries' => 'geojson',
                    'steps' => 'false',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $route = $response->json('routes.0');
            $geometry = $route['geometry'] ?? null;

            if (! is_array($route) || ! is_array($geometry) || ($geometry['type'] ?? null) !== 'LineString') {
                return null;
            }

            return [
                'type' => 'LineString',
                'source' => 'osrm',
                'profile' => $profile,
                'distance_meters' => isset($route['distance']) ? round((float) $route['distance'], 1) : null,
                'duration_seconds' => isset($route['duration']) ? round((float) $route['duration'], 1) : null,
                'coordinates' => $geometry['coordinates'] ?? [],
                'waypoints' => array_map(fn (array $waypoint): array => [
                    'lat' => round((float) ($waypoint['lat'] ?? $waypoint['latitude'] ?? 0), 8),
                    'lng' => round((float) ($waypoint['lng'] ?? $waypoint['longitude'] ?? 0), 8),
                    'order' => (int) ($waypoint['order'] ?? 0),
                    'title' => $waypoint['title'] ?? null,
                    'geocoded_place_name' => $waypoint['geocoded_place_name'] ?? null,
                ], $waypoints),
            ];
        } catch (\Throwable $e) {
            Log::info('SoundWalk routing unavailable', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array<string, mixed>>
     */
    private function resolveWaypointCoordinates(array $data): array
    {
        $waypoints = $data['waypoints'] ?? [];

        if (! is_array($waypoints)) {
            return [];
        }

        return array_map(function (array $waypoint) use ($data): array {
            $waypoint = $this->resolveArborisisPointWaypoint($waypoint);

            $explicitQuery = trim((string) ($waypoint['place_query'] ?? ''));
            $query = $explicitQuery !== '' ? $explicitQuery : $this->geocodingQuery($waypoint, $data);

            if ($query === null) {
                if (! $this->hasWaypointCoordinates($waypoint)) {
                    throw ValidationException::withMessages([
                        'waypoints' => 'Chaque arrêt de balade doit avoir un lieu géocodable ou des coordonnées fiables.',
                    ]);
                }

                return $waypoint;
            }

            $resolved = $this->geocodePlace($query);

            if ($resolved === null) {
                if ($explicitQuery !== '' || ! $this->hasWaypointCoordinates($waypoint)) {
                    throw ValidationException::withMessages([
                        'waypoints' => "Impossible de géocoder l'arrêt \"{$query}\" avec OpenStreetMap. Précise le nom du lieu, la ville et le pays.",
                    ]);
                }

                return $waypoint;
            }

            $waypoint['lat'] = $resolved['lat'];
            $waypoint['lng'] = $resolved['lng'];
            $waypoint['geocoded_place_name'] = $resolved['display_name'];
            $waypoint['geocoded_source'] = 'nominatim';

            return $waypoint;
        }, $waypoints);
    }

    /**
     * @param array<string, mixed> $waypoint
     * @return array<string, mixed>
     */
    private function resolveArborisisPointWaypoint(array $waypoint): array
    {
        $pointId = $waypoint['arborisis_point_id'] ?? null;

        if ($pointId === null || $this->hasWaypointCoordinates($waypoint)) {
            return $waypoint;
        }

        $point = ArborisisPoint::query()->find($pointId);

        if (! $point instanceof ArborisisPoint) {
            return $waypoint;
        }

        $waypoint['lat'] = $point->getPublicLatitude();
        $waypoint['lng'] = $point->getPublicLongitude();
        $waypoint['title'] = $waypoint['title'] ?? $point->title;
        $waypoint['geocoded_place_name'] = $point->title;
        $waypoint['geocoded_source'] = 'arborisis_point';

        return $waypoint;
    }

    /**
     * @param array<string, mixed> $waypoint
     */
    private function hasWaypointCoordinates(array $waypoint): bool
    {
        return isset($waypoint['lat'], $waypoint['lng'])
            && is_numeric($waypoint['lat'])
            && is_numeric($waypoint['lng'])
            && (float) $waypoint['lat'] >= -90
            && (float) $waypoint['lat'] <= 90
            && (float) $waypoint['lng'] >= -180
            && (float) $waypoint['lng'] <= 180;
    }

    /**
     * @param array<string, mixed> $waypoint
     * @param array<string, mixed> $data
     */
    private function geocodingQuery(array $waypoint, array $data): ?string
    {
        $explicit = trim((string) ($waypoint['place_query'] ?? ''));

        if ($explicit !== '') {
            return $explicit;
        }

        if (! (bool) config('services.nominatim.infer_sound_walk_waypoints', true)) {
            return null;
        }

        $title = trim((string) ($waypoint['title'] ?? ''));

        if ($title === '' || mb_strlen($title) < 4) {
            return null;
        }

        $context = collect([
            $data['title'] ?? null,
            $data['audio_environment_type'] ?? null,
            ...($data['tags'] ?? []),
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => trim($value))
            ->take(4)
            ->implode(', ');

        return trim($title.($context !== '' ? ', '.$context : ''));
    }

    /**
     * @return array{lat: float, lng: float, display_name: string}|null
     */
    private function geocodePlace(string $query): ?array
    {
        if (! (bool) config('services.nominatim.enabled', true)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'User-Agent' => (string) config('services.nominatim.user_agent', config('app.name', 'Arborisis').'/1.0'),
            ])
                ->timeout((int) config('services.nominatim.timeout', 8))
                ->get((string) config('services.nominatim.url', 'https://nominatim.openstreetmap.org/search'), [
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'q' => $query,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $result = $response->json()[0] ?? null;

            if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
                return null;
            }

            return [
                'lat' => round((float) $result['lat'], 8),
                'lng' => round((float) $result['lon'], 8),
                'display_name' => Str::limit((string) ($result['display_name'] ?? $query), 500, ''),
            ];
        } catch (\Throwable $e) {
            Log::info('SoundWalk waypoint geocoding unavailable', [
                'query' => Str::limit($query, 160),
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
