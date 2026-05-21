<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AudioAnalysis\CallbackRequest;
use App\Services\AudioAnalysis\AudioAnalysisCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InternalAudioAnalysisController extends Controller
{
    public function __construct(
        private AudioAnalysisCallbackService $callbackService,
    ) {}

    public function callback(CallbackRequest $request): JsonResponse
    {
        $payload = $request->validated();

        try {
            $analysis = $this->callbackService->handle($payload);

            return response()->json([
                'message' => 'Callback processed.',
                'analysis_id' => $analysis->id,
                'status' => $analysis->status->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('InternalAudioAnalysis callback error.', [
                'sound_id' => $payload['sound_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to process callback.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }
}
