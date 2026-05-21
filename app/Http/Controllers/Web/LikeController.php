<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sound;
use App\Services\Social\LikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function __construct(
        private readonly LikeService $likeService
    ) {}

    public function store(Request $request, Sound $sound): JsonResponse
    {
        $this->authorize('like', $sound);

        $result = $this->likeService->like($request->user(), $sound);

        return response()->json($result);
    }

    public function destroy(Request $request, Sound $sound): JsonResponse
    {
        $this->authorize('like', $sound);

        $result = $this->likeService->unlike($request->user(), $sound);

        return response()->json($result);
    }
}
