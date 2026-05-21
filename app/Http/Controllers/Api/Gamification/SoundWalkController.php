<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Http\Controllers\Controller;
use App\Models\SoundWalk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoundWalkController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = SoundWalk::query()
            ->with(['user:id,name,slug', 'points']);

        $query->where(function ($q) {
            $q->public();
            if (auth()->check()) {
                $q->orWhere('user_id', auth()->id());
            }
        });

        if ($request->filled('bounds')) {
            $bounds = $request->array('bounds');
            $query->whereBetween('approximate_start_latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('approximate_start_longitude', [$bounds['west'], $bounds['east']]);
        }

        $soundWalks = $query->get();

        $features = $soundWalks->map(function (SoundWalk $soundWalk) {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'LineString',
                    'coordinates' => $this->routeCoordinates($soundWalk),
                ],
                'properties' => [
                    'id' => $soundWalk->id,
                    'slug' => $soundWalk->slug,
                    'title' => $soundWalk->title,
                    'description' => $soundWalk->description,
                    'difficulty_level' => $soundWalk->difficulty_level,
                    'estimated_duration_minutes' => $soundWalk->estimated_duration_minutes,
                    'route_distance_meters' => $soundWalk->route_geometry['distance_meters'] ?? null,
                    'route_duration_seconds' => $soundWalk->route_geometry['duration_seconds'] ?? null,
                    'route_source' => $soundWalk->route_geometry['source'] ?? null,
                    'tags' => $soundWalk->tags ?? [],
                    'cover_image' => $soundWalk->cover_image,
                    'moderation_status' => $soundWalk->moderation_status?->value,
                    'created_at' => $soundWalk->created_at?->format('d/m/Y'),
                    'user' => $soundWalk->user?->only('id', 'name', 'slug'),
                    'waypoints_count' => $soundWalk->points->count(),
                ],
            ];
        });

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]);
    }

    public function show(Request $request, SoundWalk $soundWalk): JsonResponse
    {
        $this->authorize('view', $soundWalk);

        return response()->json([
            'id' => $soundWalk->id,
            'slug' => $soundWalk->slug,
            'title' => $soundWalk->title,
            'description' => $soundWalk->description,
            'start_latitude' => $soundWalk->getPublicStartLatitude(),
            'start_longitude' => $soundWalk->getPublicStartLongitude(),
            'difficulty_level' => $soundWalk->difficulty_level,
            'estimated_duration_minutes' => $soundWalk->estimated_duration_minutes,
            'route_distance_meters' => $soundWalk->route_geometry['distance_meters'] ?? null,
            'route_duration_seconds' => $soundWalk->route_geometry['duration_seconds'] ?? null,
            'tags' => $soundWalk->tags,
            'cover_image' => $soundWalk->cover_image,
            'audio_environment_type' => $soundWalk->audio_environment_type,
            'route_geometry' => $soundWalk->route_geometry,
            'user' => $soundWalk->user?->only('id', 'name', 'slug'),
            'created_at' => $soundWalk->created_at?->format('d/m/Y'),
            'moderation_status' => $soundWalk->moderation_status?->value,
            'points' => $soundWalk->points->map(fn ($point) => [
                'id' => $point->id,
                'order' => $point->order,
                'title' => $point->title,
                'description' => $point->description,
                'latitude' => round((float) $point->latitude, 5),
                'longitude' => round((float) $point->longitude, 5),
                'stop_metadata' => $point->stop_metadata,
            ]),
        ]);
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private function routeCoordinates(SoundWalk $soundWalk): array
    {
        $geometry = $soundWalk->route_geometry ?? [];

        if (isset($geometry['coordinates']) && is_array($geometry['coordinates'])) {
            return $geometry['coordinates'];
        }

        if (array_is_list($geometry)) {
            return collect($geometry)
                ->map(function (array $waypoint): ?array {
                    $lat = $waypoint['lat'] ?? $waypoint['latitude'] ?? null;
                    $lng = $waypoint['lng'] ?? $waypoint['longitude'] ?? null;

                    if ($lat === null || $lng === null) {
                        return null;
                    }

                    return [(float) $lng, (float) $lat];
                })
                ->filter()
                ->values()
                ->all();
        }

        return [];
    }
}
