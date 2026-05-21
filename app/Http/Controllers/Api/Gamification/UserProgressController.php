<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Http\Controllers\Controller;
use App\Services\Gamification\XpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserProgressController extends Controller
{
    public function __construct(
        private readonly XpService $xpService,
    ) {
    }

    public function progress(Request $request): JsonResponse
    {
        $user = $request->user();

        $xpForNext = $this->xpService->xpForNextLevel($user->level);
        $xpForCurrent = $this->xpService->xpForNextLevel($user->level - 1);
        $xpProgress = $user->xp_total - $xpForCurrent;
        $xpNeeded = $xpForNext - $xpForCurrent;

        return response()->json([
            'level' => $user->level,
            'xp_total' => $user->xp_total,
            'xp_for_next_level' => $xpForNext,
            'xp_progress' => $xpProgress,
            'xp_needed' => $xpNeeded,
            'progress_percentage' => min(100, (int) round(($xpProgress / max($xpNeeded, 1)) * 100)),
            'current_streak' => $user->current_streak,
            'longest_streak' => $user->longest_streak,
            'quests_completed' => $user->questProgress()->where('status', 'completed')->count(),
            'achievements_unlocked' => $user->achievements()->count(),
            'medals_unlocked' => $user->medals()->count(),
            'points_visited' => $user->arborisisVisits()->valid()->distinct('arborisis_point_id')->count(),
        ]);
    }

    public function xpEvents(Request $request): JsonResponse
    {
        $events = $request->user()
            ->xpEvents()
            ->latest()
            ->paginate(30);

        return response()->json($events);
    }
}
