<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Http\Controllers\Controller;
use App\Models\GroupRecordingEvent;
use App\Services\Gamification\GeoValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupRecordingEventController extends Controller
{
    public function __construct(
        private readonly GeoValidationService $geoService,
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'event_type' => ['required', 'string', 'in:dawn_chorus,soundwalk,night_ambience,freestyle'],
            'max_participants' => ['nullable', 'integer', 'min:2', 'max:50'],
        ]);

        $event = GroupRecordingEvent::create([
            'creator_id' => $request->user()->id,
            ...$validated,
            'max_participants' => $validated['max_participants'] ?? 10,
        ]);

        $event->participants()->create([
            'user_id' => $request->user()->id,
            'status' => 'joined',
        ]);

        return response()->json([
            'message' => 'Événement créé.',
            'event' => $event->load('participants:user_id'),
        ], 201);
    }

    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:50'],
        ]);

        $events = GroupRecordingEvent::upcoming()
            ->nearby(
                (float) $validated['lat'],
                (float) $validated['lng'],
                $validated['radius'] ?? 10
            )
            ->withCount('participants')
            ->orderBy('scheduled_at')
            ->limit(50)
            ->get();

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $events->map(fn ($e) => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $e->longitude, (float) $e->latitude],
                ],
                'properties' => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'description' => $e->description,
                    'event_type' => $e->event_type,
                    'scheduled_at' => $e->scheduled_at->toIso8601String(),
                    'max_participants' => $e->max_participants,
                    'participants_count' => $e->participants_count,
                    'status' => $e->status,
                    'creator_id' => $e->creator_id,
                ],
            ])->values(),
        ]);
    }

    public function join(Request $request, GroupRecordingEvent $event): JsonResponse
    {
        if ($event->status !== 'upcoming') {
            return response()->json(['message' => 'Cet événement n\'est plus ouvert.'], 422);
        }

        if ($event->participants()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Tu participes déjà à cet événement.'], 422);
        }

        if ($event->participants()->count() >= $event->max_participants) {
            return response()->json(['message' => 'Cet événement est complet.'], 422);
        }

        $event->participants()->create([
            'user_id' => $request->user()->id,
            'status' => 'joined',
        ]);

        return response()->json(['message' => 'Tu as rejoint l\'événement.']);
    }

    public function leave(Request $request, GroupRecordingEvent $event): JsonResponse
    {
        $event->participants()
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['message' => 'Tu as quitté l\'événement.']);
    }

    public function checkIn(Request $request, GroupRecordingEvent $event): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:500'],
        ]);

        $participant = $event->participants()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $participant) {
            return response()->json(['message' => 'Tu ne participes pas à cet événement.'], 422);
        }

        $isNearby = $this->geoService->isWithinRange(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            (float) $event->latitude,
            (float) $event->longitude,
            150 // 150m radius for event check-in
        );

        if (! $isNearby) {
            return response()->json(['message' => 'Tu n\'es pas assez proche du lieu de l\'événement.'], 422);
        }

        $participant->update(['status' => 'checked_in']);

        return response()->json(['message' => 'Check-in validé !']);
    }
}
