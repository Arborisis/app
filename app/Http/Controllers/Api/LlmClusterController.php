<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LlmModel;
use App\Models\LlmJob;
use App\Services\LlmClusterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LlmClusterController extends Controller
{
    public function __construct(
        private LlmClusterService $llmService
    ) {}

    /**
     * Liste les modèles LLM disponibles.
     */
    public function listModels(): JsonResponse
    {
        $models = LlmModel::active()-/>get();

        return response()->json([
            'models' => $models->map(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'description' => $model->description,
                    'type' => $model->type,
                    'requirements' => $model->requirements,
                    'is_default' => $model->is_default,
                ];
            }),
        ]);
    }

    /**
     * Crée un job d'inférence LLM.
     */
    public function inference(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:50000',
            'model' => 'nullable|string|exists:llm_models,slug',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:16000',
            'stream' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $job = $this->llmService->createInferenceJob(
            $request->input('prompt'),
            [
                'model' => $request->input('model'),
                'temperature' => $request->input('temperature', 0.7),
                'max_tokens' => $request->input('max_tokens', 2048),
                'stream' => $request->input('stream', false),
            ]
        );

        if (!$job) {
            return response()->json([
                'error' => 'Failed to create inference job. Cluster may be at capacity.',
            ], 503);
        }

        // Si le job est déjà complété (API direct), retourner le résultat
        if ($job->status === 'completed') {
            return response()->json([
                'job' => [
                    'id' => $job->id,
                    'status' => $job->status,
                    'model' => $job->model->slug,
                    'response' => $job->response,
                    'input_tokens' => $job->input_tokens,
                    'output_tokens' => $job->output_tokens,
                    'processing_time_ms' => $job->processing_time_ms,
                ],
            ]);
        }

        // Sinon retourner le job en queue
        return response()->json([
            'job' => [
                'id' => $job->id,
                'status' => $job->status,
                'model' => $job->model->slug,
                'queued_at' => $job->queued_at,
            ],
        ], 202);
    }

    /**
     * Récupère le statut d'un job.
     */
    public function getJobStatus(int $id): JsonResponse
    {
        $job = LlmJob::with(['model', 'worker.audioWorker'])->find($id);

        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        return response()->json([
            'job' => [
                'id' => $job->id,
                'status' => $job->status,
                'model' => $job->model->slug,
                'response' => $job->status === 'completed' ? $job->response : null,
                'error' => $job->status === 'failed' ? $job->error_message : null,
                'input_tokens' => $job->input_tokens,
                'output_tokens' => $job->output_tokens,
                'processing_time_ms' => $job->processing_time_ms,
                'tokens_per_second' => $job->tokens_per_second,
                'worker' => $job->worker ? $job->worker->audioWorker->name : null,
                'queued_at' => $job->queued_at,
                'started_at' => $job->started_at,
                'completed_at' => $job->completed_at,
            ],
        ]);
    }

    /**
     * Récupère les statistiques du cluster LLM.
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->llmService->getStats();

        return response()->json($stats);
    }

    /**
     * Liste les jobs avec filtres.
     */
    public function listJobs(Request $request): JsonResponse
    {
        $query = LlmJob::with(['model', 'worker.audioWorker'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('model')) {
            $model = LlmModel::where('slug', $request->input('model'))->first();
            if ($model) {
                $query->where('llm_model_id', $model->id);
            }
        }

        $jobs = $query->limit($request->input('limit', 50))->get();

        return response()->json([
            'jobs' => $jobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'status' => $job->status,
                    'model' => $job->model->slug,
                    'input_tokens' => $job->input_tokens,
                    'output_tokens' => $job->output_tokens,
                    'processing_time_ms' => $job->processing_time_ms,
                    'queued_at' => $job->queued_at,
                ];
            }),
        ]);
    }

    /**
     * Un worker demande un job LLM à exécuter.
     */
    public function requestJob(Request $request): JsonResponse
    {
        $worker = \App\Models\LlmWorker::whereHas('audioWorker', function ($q) use ($request) {
            $q->where('token', $request->bearerToken());
        })->first();

        if (!$worker || !$worker->isAvailable()) {
            return response()->json(['error' => 'Worker not available'], 403);
        }

        // Chercher un job assigné ou un job en queue
        $job = LlmJob::where(function ($query) use ($worker) {
            $query->where('llm_worker_id', $worker->id)
                ->where('status', 'processing');
        })->orWhere(function ($query) use ($worker) {
            $query->where('status', 'queued')
                ->whereHas('model', function ($q) use ($worker) {
                    $q->whereIn('slug', $worker->capabilities ?? []);
                });
        })
        ->orderBy('queued_at')
        ->with('model')
        ->first();

        if ($job && $job->status === 'queued') {
            $job->markProcessing($worker->id);
            $worker->markBusy();
        }

        if ($job) {
            return response()->json([
                'job' => [
                    'id' => $job->id,
                    'model' => $job->model->slug,
                    'prompt' => $job->prompt,
                    'metadata' => $job->metadata,
                ],
            ]);
        }

        return response()->json(['job' => null], 204);
    }

    /**
     * Soumet le résultat d'un job LLM.
     */
    public function submitResult(Request $request, int $jobId): JsonResponse
    {
        $worker = \App\Models\LlmWorker::whereHas('audioWorker', function ($q) use ($request) {
            $q->where('token', $request->bearerToken());
        })->first();

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $job = LlmJob::where('id', $jobId)
            ->where('llm_worker_id', $worker->id)
            ->first();

        if (!$job) {
            return response()->json(['error' => 'Job not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,failed',
            'response' => 'nullable|string',
            'input_tokens' => 'nullable|integer|min:0',
            'output_tokens' => 'nullable|integer|min:0',
            'processing_time_ms' => 'required|integer|min:0',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->input('status') === 'completed') {
            $job->markCompleted(
                $request->input('response', ''),
                $request->input('input_tokens', 0),
                $request->input('output_tokens', 0),
                $request->input('processing_time_ms')
            );
            
            $worker->updateStats(
                $request->input('input_tokens', 0),
                $request->input('output_tokens', 0),
                $request->input('processing_time_ms')
            );
            
            $worker->markOnline();
        } else {
            $job->markFailed($request->input('error_message', 'Unknown error'));
            $worker->markOnline();
        }

        return response()->json(['status' => 'ok']);
    }
}