<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Enums\PresenceVisibilityMode;
use App\Http\Controllers\Controller;
use App\Services\Gamification\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function __construct(
        private readonly PresenceService $presenceService,
    ) {
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'visibility_mode' => ['required', 'string', 'in:invisible,approximate,friends_only,public_zone'],
        ]);

        $presence = $this->presenceService->updatePresence(
            $request->user(),
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            PresenceVisibilityMode::from($validated['visibility_mode']),
        );

        return response()->json([
            'message' => 'Présence mise à jour.',
            'presence' => [
                'latitude' => (float) $presence->approximate_latitude,
                'longitude' => (float) $presence->approximate_longitude,
                'expires_at' => $presence->expires_at,
                'visibility_mode' => $presence->visibility_mode->value,
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->presenceService->removePresence($request->user());

        return response()->json([
            'message' => 'Présence supprimée. Tu es maintenant invisible sur la carte.',
        ]);
    }

    public function mapPresence(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'bounds' => ['required', 'array'],
            'bounds.south' => ['required', 'numeric'],
            'bounds.north' => ['required', 'numeric'],
            'bounds.west' => ['required', 'numeric'],
            'bounds.east' => ['required', 'numeric'],
        ]);

        $bounds = $validated['bounds'];
        $presences = $this->presenceService->getVisiblePresences(
            (float) $bounds['south'],
            (float) $bounds['north'],
            (float) $bounds['west'],
            (float) $bounds['east'],
            $request->user(),
        );

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => array_map(fn ($p) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$p['longitude'], $p['latitude']],
                ],
                'properties' => [
                    'user_id' => $p['user_id'],
                    'user_name' => $p['user_name'],
                    'last_seen_at' => $p['last_seen_at'],
                    'visibility_mode' => $p['visibility_mode'],
                ],
            ], $presences),
        ]);
    }
}
