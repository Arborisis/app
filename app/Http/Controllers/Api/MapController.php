<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Map\SearchMapRequest;
use App\Models\Sound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MapController extends Controller
{
    private const MAX_SOUNDS = 500;
    private const CACHE_TTL = 300; // 5 minutes

    public function sounds(Request $request): JsonResponse
    {
        $cacheKey = $this->buildCacheKey($request);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($request): JsonResponse {
            $query = Sound::public()
                ->select([
                    'id', 'slug', 'title', 'description', 'duration',
                    'user_id', 'category_id', 'play_count', 'like_count',
                    'recorded_at', 'cover_image',
                ])
                ->with([
                    'user:id,name',
                    'category:id,name,slug',
                    'soundLocation:id,sound_id,public_latitude,public_longitude,location_name',
                    'soundFile:id,sound_id,path,disk',
                ])
                ->whereHas('soundLocation', function ($q) {
                    $q->whereNotNull('public_latitude')
                        ->whereNotNull('public_longitude');
                });

            // Geo bounds filtering for performance
            if ($request->filled('bounds')) {
                $bounds = $request->array('bounds');
                if (isset($bounds['south'], $bounds['west'], $bounds['north'], $bounds['east'])) {
                    $query->whereHas('soundLocation', function ($q) use ($bounds) {
                        $q->whereBetween('public_latitude', [$bounds['south'], $bounds['north']])
                            ->whereBetween('public_longitude', [$bounds['west'], $bounds['east']]);
                    });
                }
            }

            if ($request->filled('category')) {
                $query->where('category_id', $request->integer('category'));
            }

            if ($request->filled('environment')) {
                $query->where('environment_id', $request->integer('environment'));
            }

            $sounds = $query->latest()
                ->limit(self::MAX_SOUNDS)
                ->get();

            $features = $sounds->map(function (Sound $sound) {
                $location = $sound->soundLocation;

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $location->public_longitude,
                            (float) $location->public_latitude,
                        ],
                    ],
                    'properties' => [
                        'id' => $sound->id,
                        'title' => $sound->title,
                        'slug' => $sound->slug,
                        'description' => $sound->description,
                        'duration' => $sound->duration,
                        'user_name' => $sound->user?->name ?? 'Anonyme',
                        'category' => $sound->category?->name,
                        'cover_url' => $sound->cover_url,
                        'play_count' => $sound->play_count,
                        'like_count' => $sound->like_count,
                        'location_name' => $location->location_name,
                        'recorded_at' => $sound->recorded_at?->toIso8601String(),
                    ],
                ];
            });

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        });
    }

    public function search(SearchMapRequest $request): JsonResponse
    {
        $query = $request->validated('q');
        $cacheKey = 'map:search:'.md5($query);

        return Cache::remember($cacheKey, 60, function () use ($query): JsonResponse {
            $sounds = Sound::public()
                ->select([
                    'id', 'slug', 'title', 'duration',
                    'user_id', 'category_id', 'play_count', 'like_count',
                    'recorded_at', 'cover_image',
                ])
                ->with([
                    'user:id,name',
                    'category:id,name',
                    'soundLocation:id,sound_id,public_latitude,public_longitude,location_name',
                    'soundFile:id,sound_id,path,disk',
                ])
                ->whereHas('soundLocation', function ($q) {
                    $q->whereNotNull('public_latitude')
                        ->whereNotNull('public_longitude');
                })
                ->where(function ($q) use ($query) {
                    $q->where('title', 'ilike', "%{$query}%")
                        ->orWhere('description', 'ilike', "%{$query}%")
                        ->orWhereHas('soundLocation', function ($ql) use ($query) {
                            $ql->where('location_name', 'ilike', "%{$query}%");
                        });
                })
                ->limit(20)
                ->get();

            $features = $sounds->map(function (Sound $sound) {
                $location = $sound->soundLocation;

                return [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $location->public_longitude,
                            (float) $location->public_latitude,
                        ],
                    ],
                    'properties' => [
                        'id' => $sound->id,
                        'title' => $sound->title,
                        'slug' => $sound->slug,
                        'user_name' => $sound->user?->name ?? 'Anonyme',
                        'category' => $sound->category?->name,
                        'duration' => $sound->duration,
                        'cover_url' => $sound->cover_url,
                        'location_name' => $location->location_name,
                        'recorded_at' => $sound->recorded_at?->toIso8601String(),
                    ],
                ];
            });

            return response()->json([
                'type' => 'FeatureCollection',
                'features' => $features,
            ]);
        });
    }

    private function buildCacheKey(Request $request): string
    {
        $parts = ['map:sounds'];

        if ($request->filled('bounds')) {
            $bounds = $request->array('bounds');
            $parts[] = sprintf(
                'b:%.3f,%.3f,%.3f,%.3f',
                $bounds['south'] ?? 0,
                $bounds['west'] ?? 0,
                $bounds['north'] ?? 0,
                $bounds['east'] ?? 0
            );
        }

        if ($request->filled('category')) {
            $parts[] = 'c:'.$request->integer('category');
        }

        if ($request->filled('environment')) {
            $parts[] = 'e:'.$request->integer('environment');
        }

        return implode(':', $parts);
    }
}
