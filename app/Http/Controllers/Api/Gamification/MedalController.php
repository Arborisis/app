<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Http\Controllers\Controller;
use App\Models\Medal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $medals = Medal::active()
            ->orderBy('category')
            ->orderBy('rarity')
            ->get();

        return response()->json($medals);
    }

    public function myMedals(Request $request): JsonResponse
    {
        $medals = $request->user()
            ->medals()
            ->withPivot('unlocked_at', 'source_type')
            ->get();

        return response()->json($medals);
    }
}
