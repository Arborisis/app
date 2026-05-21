<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SoundWalk;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SoundWalkController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('SoundWalks/Index');
    }

    public function show(Request $request, string $slug): Response
    {
        $soundWalk = SoundWalk::with(['user:id,name,slug', 'points'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->authorize('view', $soundWalk);

        return Inertia::render('SoundWalks/Show', [
            'soundWalk' => [
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
                'tags' => $soundWalk->tags ?? [],
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
            ],
        ]);
    }
}
