<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClusterModel;
use App\Models\ClusterTask;
use App\Services\ClusterIAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClusterController extends Controller
{
    public function __construct(
        private ClusterIAService $clusterService
    ) {}

    /**
     * Liste les modèles de cluster disponibles.
     */
    public function listModels(): JsonResponse
    {
        $models = ClusterModel::active()
            ->withCount(['tasks' => function ($query) {
                $query->where('status', 'processing');
            }])
            ->get();

        return response()->json([
            'models' => $models->map(function ($model) {
                return [
                    'id' => $model->id,
                    'name' => $model->name,
                    'slug' => $model->slug,
                    'description' => $model->description,
                    'type' => $model->type,
                    'requirements' => $model->requirements,
                    'active_tasks' => $model->tasks_count,
                    'is_default' => $model->is_default,
                ];
            }),
        ]);
    }

    /**
     * Crée une tâche d'inférence sur le cluster.
     */
    public function createTask(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'model' => 'required|string|exists:cluster_models,slug',
            'type' => 'nullable|in:inference,training,embedding,analysis',
            'payload' => 'required|array',
            'payload.prompt' => 'required|string',
            'payload.max_tokens' => 'nullable|integer|min:1|max:16000',
            'payload.temperature' => 'nullable|numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $task = $this->clusterService->createInferenceTask(
            $request->input('model'),
            $request->input('payload'),
            $request->input('type', 'inference')
        );

        if (!$task) {
            return response()->json([
                'error' => 'Failed to create task. No workers available and fallback failed.',
            ], 503);
        }

        return response()->json([
            'task' => [
                'id' => $task->id,
                'status' => $task->status,
                'model' => $task->clusterModel->slug,
                'queued_at' => $task->queued_at,
            ],
        ], 201);
    }

    /**
     * Récupère le statut d'une tâche.
     */
    public function getTaskStatus(int $id): JsonResponse
    {
        $task = ClusterTask::with(['clusterModel', 'audioWorker'])->find($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        return response()->json([
            'task' => [
                'id' => $task->id,
                'status' => $task->status,
                'model' => $task->clusterModel->slug,
                'type' => $task->type,
                'worker' => $task->audioWorker ? $task->audioWorker->name : null,
                'result' => $task->status === 'completed' ? $task->result : null,
                'error' => $task->status === 'failed' ? $task->error_message : null,
                'processing_time' => $task->processing_time_seconds,
                'queued_at' => $task->queued_at,
                'started_at' => $task->started_at,
                'completed_at' => $task->completed_at,
            ],
        ]);
    }

    /**
     * Récupère les statistiques du cluster.
     */
    public function getStats(): JsonResponse
    {
        $stats = $this->clusterService->getClusterStats();

        return response()->json($stats);
    }

    /**
     * Liste les tâches avec filtres.
     */
    public function listTasks(Request $request): JsonResponse
    {
        $query = ClusterTask::with(['clusterModel', 'audioWorker'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('model')) {
            $model = ClusterModel::where('slug', $request->input('model'))->first();
            if ($model) {
                $query->where('cluster_model_id', $model->id);
            }
        }

        $tasks = $query->limit($request->input('limit', 50))->get();

        return response()->json([
            'tasks' => $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'status' => $task->status,
                    'model' => $task->clusterModel->slug,
                    'type' => $task->type,
                    'worker' => $task->audioWorker ? $task->audioWorker->name : null,
                    'processing_time' => $task->processing_time_seconds,
                    'queued_at' => $task->queued_at,
                ];
            }),
        ]);
    }

    /**
     * Un worker demande une tâche cluster à exécuter.
     */
    public function requestClusterTask(Request $request): JsonResponse
    {
        $worker = AudioWorker::where('token', $request->bearerToken())->first();

        if (!$worker || !$worker->isAvailable()) {
            return response()->json(['error' => 'Worker not available'], 403);
        }

        // Chercher une tâche assignée à ce worker
        $assignedTask = ClusterTask::where('audio_worker_id', $worker->id)
            ->where('status', 'assigned')
            ->with('clusterModel')
            ->first();

        if ($assignedTask) {
            return response()->json([
                'task' => [
                    'id' => $assignedTask->id,
                    'type' => $assignedTask->type,
                    'model' => $assignedTask->clusterModel->slug,
                    'payload' => $assignedTask->payload,
                ],
            ]);
        }

        return response()->json(['task' => null], 204);
    }

    /**
     * Soumet le résultat d'une tâche cluster.
     */
    public function submitClusterResult(Request $request, int $taskId): JsonResponse
    {
        $worker = AudioWorker::where('token', $request->bearerToken())->first();

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $task = ClusterTask::where('id', $taskId)
            ->where('audio_worker_id', $worker->id)
            ->first();

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:completed,failed',
            'processing_time_seconds' => 'required|integer|min:0',
            'result' => 'nullable|array',
            'error_message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->input('status') === 'completed') {
            $task->markCompleted(
                $request->input('result', []),
                $request->input('processing_time_seconds')
            );
            
            $worker->increment('total_jobs_completed');
        } else {
            $task->markFailed($request->input('error_message', 'Unknown error'));
            $worker->increment('total_jobs_failed');
        }

        return response()->json(['status' => 'ok']);
    }
}