<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Radio\RadioHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthRadioController extends Controller
{
    public function __invoke(Request $request, RadioHealthService $health): JsonResponse
    {
        $token = config('radio.internal_token');
        if ($token && $request->bearerToken() !== $token && ! $request->user()?->can('access-radio-manager')) {
            abort(403);
        }

        return response()->json($health->report());
    }
}
