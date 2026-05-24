<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AudioWorker;
use App\Models\ClusterModel;
use App\Models\ClusterTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClusterIAService
{
    private const TIMEOUT_MINUTES = 60;
    private const MAX_RETRIES = 2;

    /**
     * Crée une tâche d'inférence IA et la distribue sur le cluster.
     */
    public function createInferenceTask(string $modelSlug, array $payload, string $type = 'inference'): ?ClusterTask
    {
        $model = ClusterModel::where('slug', $modelSlug)->first();
        
        if (!$model) {
            Log::error("Cluster model not found: {$modelSlug}");
            return null;
        }

        if ($model->type === 'api') {
            // Pour les modèles API (Claude Opus), on exécute directement
            return $this->executeApiTask($model, $payload, $type);
        }

        // Pour les modèles locaux (Sylve), on crée une tâche cluster
        $task = ClusterTask::create([
            'cluster_model_id' => $model->id,
            'type' => $type,
            'status' => 'queued',
            'payload' => $payload,
            'queued_at' => now(),
        ]);

        // Essayer de dispatcher immédiatement
        $this->dispatchTask($task);

        return $task;
    }

    /**
     * Distribue les tâches en attente sur le cluster.
     */
    public function dispatchPendingTasks(): int
    {
        $dispatched = 0;
        
        $pendingTasks = ClusterTask::where('status', 'queued')
            ->orderBy('queued_at')
            ->limit(50)
            ->get();

        foreach ($pendingTasks as $task) {
            if ($this->dispatchTask($task)) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    /**
     * Dispatch une tâche spécifique sur un worker disponible.
     */
    public function dispatchTask(ClusterTask $task): bool
    {
        $model = $task->clusterModel;
        
        if (!$model) {
            $task->markFailed('Model not found');
            return false;
        }

        // Trouver le meilleur worker pour cette tâche
        $worker = $this->findBestWorkerForModel($model);
        
        if (!$worker) {
            Log::warning("No available worker for model: {$model->slug}");
            
            // Si c'est un modèle hybride avec fallback, utiliser le fallback
            if ($model->type === 'hybrid' && $model->fallback_model) {
                return $this->fallbackToApi($task, $model);
            }
            
            return false;
        }

        try {
            $task->markAssigned($worker->id);
            
            Log::info('Cluster task dispatched', [
                'task_id' => $task->id,
                'model' => $model->slug,
                'worker_id' => $worker->id,
            ]);

            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch cluster task', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Exécute une tâche via API (Claude Opus fallback).
     */
    private function executeApiTask(ClusterModel $model, array $payload, string $type): ClusterTask
    {
        $startTime = now();
        
        try {
            $config = $model->config;
            $apiKey = $config['api_key'] ?? null;
            $endpoint = $config['endpoint'] ?? null;
            
            if (!$apiKey || !$endpoint) {
                throw new \Exception('API configuration missing');
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($endpoint, $payload);

            $result = $response->json();
            $processingTime = (int) $startTime->diffInSeconds(now());

            return ClusterTask::create([
                'cluster_model_id' => $model->id,
                'type' => $type,
                'status' => 'completed',
                'payload' => $payload,
                'result' => $result,
                'processing_time_seconds' => $processingTime,
                'queued_at' => $startTime,
                'started_at' => $startTime,
                'completed_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('API task failed', [
                'model' => $model->slug,
                'error' => $e->getMessage(),
            ]);

            return ClusterTask::create([
                'cluster_model_id' => $model->id,
                'type' => $type,
                'status' => 'failed',
                'payload' => $payload,
                'error_message' => $e->getMessage(),
                'queued_at' => $startTime,
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Fallback vers API quand aucun worker local n'est disponible.
     */
    private function fallbackToApi(ClusterTask $task, ClusterModel $model): bool
    {
        $fallbackModel = $model->getFallbackModel();
        
        if (!$fallbackModel || $fallbackModel->type !== 'api') {
            return false;
        }

        Log::info('Falling back to API model', [
            'task_id' => $task->id,
            'original_model' => $model->slug,
            'fallback_model' => $fallbackModel->slug,
        ]);

        $result = $this->executeApiTask($fallbackModel, $task->payload, $task->type);
        
        if ($result->status === 'completed') {
            $task->update([
                'status' => 'completed',
                'result' => $result->result,
                'processing_time_seconds' => $result->processing_time_seconds,
                'completed_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Trouve le meilleur worker pour un modèle donné.
     */
    private function findBestWorkerForModel(ClusterModel $model): ?AudioWorker
    {
        $workers = AudioWorker::available()
            ->withCount(['assignments' => function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            }])
            ->get();

        $eligibleWorkers = $workers->filter(function ($worker) use ($model) {
            return $model->canRunOnWorker($worker) && $worker->assignments_count < 2;
        });

        if ($eligibleWorkers->isEmpty()) {
            return null;
        }

        // Scorer les workers
        return $eligibleWorkers
            ->sortByDesc(function ($worker) use ($model) {
                $score = $worker->getCapabilityScore();
                
                // Bonus GPU si le modèle en a besoin
                $reqs = $model->requirements;
                if (($reqs['requires_gpu'] ?? false) && $worker->has_gpu) {
                    $score += 1000;
                }
                
                // Pénalité de charge
                $score -= $worker->assignments_count * 200;
                
                // Bonus temps de traitement historique
                if ($worker->avg_processing_time > 0) {
                    $score += max(0, 300 - $worker->avg_processing_time);
                }

                return $score;
            })
            ->first();
    }

    /**
     * Récupère les statistiques du cluster.
     */
    public function getClusterStats(): array
    {
        $totalWorkers = AudioWorker::count();
        $availableWorkers = AudioWorker::available()->count();
        $totalGpuWorkers = AudioWorker::where('has_gpu', true)->whereIn('status', ['online', 'busy'])->count();
        
        $totalCpuCores = AudioWorker::whereIn('status', ['online', 'busy'])->sum('cpu_cores');
        $totalMemoryGb = AudioWorker::whereIn('status', ['online', 'busy'])->sum('memory_gb');

        return [
            'workers' => [
                'total' => $totalWorkers,
                'available' => $availableWorkers,
                'gpu_enabled' => $totalGpuWorkers,
            ],
            'resources' => [
                'total_cpu_cores' => (int) $totalCpuCores,
                'total_memory_gb' => (int) $totalMemoryGb,
                'compute_score' => (int) (($totalCpuCores * 100) + ($totalMemoryGb * 50)),
            ],
            'tasks' => [
                'queued' => ClusterTask::where('status', 'queued')->count(),
                'processing' => ClusterTask::where('status', 'processing')->count(),
                'completed_today' => ClusterTask::where('status', 'completed')
                    ->whereDate('completed_at', today())
                    ->count(),
            ],
            'models' => ClusterModel::active()
                ->withCount(['tasks' => function ($query) {
                    $query->where('status', 'processing');
                }])
                ->get()
                ->map(function ($model) {
                    return [
                        'id' => $model->id,
                        'name' => $model->name,
                        'slug' => $model->slug,
                        'type' => $model->type,
                        'active_tasks' => $model->tasks_count,
                    ];
                }),
        ];
    }

    /**
     * Réassigne les tâches en timeout.
     */
    public function reassignTimeoutTasks(): int
    {
        $timeoutTasks = ClusterTask::where('status', 'processing')
            ->where('started_at', '<', now()->subMinutes(self::TIMEOUT_MINUTES))
            ->get();

        $reassigned = 0;

        foreach ($timeoutTasks as $task) {
            $task->update([
                'status' => 'queued',
                'started_at' => null,
                'audio_worker_id' => null,
                'error_message' => 'Timeout, requeued',
            ]);

            $worker = $task->audioWorker;
            if ($worker) {
                $worker->markOnline();
            }

            $reassigned++;
        }

        return $reassigned;
    }
}