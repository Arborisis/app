<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\AnalysisStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AudioAnalysis\CallbackRequest;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Services\AudioAnalysis\AudioAnalysisCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Reçoit un événement de la queue Cloudflare et crée/met à jour
     * une analyse pour qu'elle soit dispatchée aux workers audio.
     */
    public function orchestrate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sound_id' => 'required|string',
            'original_r2_key' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $soundId = $request->input('sound_id');
        $r2Key = $request->input('original_r2_key');

        try {
            // Essayer de trouver le sound par ID (UUID ou int)
            $sound = Sound::where('id', $soundId)->first();

            if (!$sound) {
                Log::warning('Orchestrate: sound not found.', ['sound_id' => $soundId]);
                // On crée quand même l'analyse si le sound existe peut-être plus tard
                // mais pour l'instant on retourne une erreur
                return response()->json([
                    'message' => 'Sound not found.',
                    'sound_id' => $soundId,
                ], 404);
            }

            // Trouver ou créer l'analyse
            $analysis = SoundAnalysis::firstOrCreate(
                ['sound_id' => $sound->id],
                [
                    'status' => AnalysisStatus::PENDING,
                    'original_r2_key' => $r2Key,
                ]
            );

            // Mettre à jour la clé R2 si nécessaire
            if ($analysis->original_r2_key !== $r2Key) {
                $analysis->update(['original_r2_key' => $r2Key]);
            }

            // Remettre en queue pour dispatch aux workers
            if (!in_array($analysis->status, [AnalysisStatus::QUEUED, AnalysisStatus::PROCESSING], true)) {
                $analysis->markQueued();
            }

            Log::info('Orchestrate: analysis queued for workers.', [
                'sound_id' => $soundId,
                'analysis_id' => $analysis->id,
            ]);

            return response()->json([
                'message' => 'Analysis queued.',
                'analysis_id' => $analysis->id,
                'status' => $analysis->status->value,
            ]);
        } catch (\Throwable $e) {
            Log::error('Orchestrate error.', [
                'sound_id' => $soundId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to orchestrate analysis.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal error.',
            ], 500);
        }
    }
}
