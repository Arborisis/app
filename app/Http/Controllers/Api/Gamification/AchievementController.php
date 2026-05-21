<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $achievements = Achievement::active()
            ->orderBy('category')
            ->orderBy('points')
            ->get();

        return response()->json($achievements);
    }

    public function myAchievements(Request $request): JsonResponse
    {
        $achievements = $request->user()
            ->achievements()
            ->withPivot('unlocked_at')
            ->get();

        return response()->json($achievements);
    }
}
