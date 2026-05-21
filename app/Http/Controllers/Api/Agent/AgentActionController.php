<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\CreateItineraryRequest;
use App\Http\Requests\Agent\CreatePointRequest;
use App\Services\Gamification\ArborisisPointService;
use App\Services\Gamification\SoundWalkService;
use Illuminate\Http\JsonResponse;

class AgentActionController extends Controller
{
    public function __construct(
        private readonly ArborisisPointService $pointService,
        private readonly SoundWalkService $soundWalkService,
    ) {
    }

    public function createPoint(CreatePointRequest $request): JsonResponse
    {
        $point = $this->pointService->createPoint(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Point proposé avec succès. Il sera examiné par la modération avant publication.',
            'created_resource' => [
                'type' => 'arborisis_point',
                'id' => $point->id,
                'slug' => $point->slug,
                'title' => $point->title,
                'moderation_status' => $point->moderation_status->value,
            ],
        ], 201);
    }

    public function createItinerary(CreateItineraryRequest $request): JsonResponse
    {
        $soundWalk = $this->soundWalkService->createItinerary(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Balade field recording proposée avec succès. Elle sera examinée par la modération avant publication.',
            'created_resource' => [
                'type' => 'sound_walk',
                'id' => $soundWalk->id,
                'slug' => $soundWalk->slug,
                'title' => $soundWalk->title,
                'moderation_status' => $soundWalk->moderation_status->value,
                'waypoints_count' => $soundWalk->points()->count(),
            ],
        ], 201);
    }
}
