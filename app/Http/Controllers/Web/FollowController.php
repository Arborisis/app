<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Social\FollowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function __construct(
        private readonly FollowService $followService
    ) {}

    public function store(Request $request, User $user): JsonResponse
    {
        $this->authorize('follow', $user);

        $result = $this->followService->follow($request->user(), $user);

        return response()->json($result);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('unfollow', $user);

        $result = $this->followService->unfollow($request->user(), $user);

        return response()->json($result);
    }
}
