<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LlmModel;
use App\Models\LlmWorker;
use App\Models\LlmJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LlmClusterService
{
    private const JOB_TIMEOUT_MINUTES = 10;
    private const HEARTBEAT_TIMEOUT_MINUTES = 2;

    /**
     * Crée un job d'inférence LLM et le distribue sur le cluster.
     */
    public function createInferenceJob(string $prompt, array $options = []): ?LlmJob
    {
        $modelSlug = $options['model'] ?? 'sylve';
        $model = LlmModel::where('slug', $modelSlug)->first();
        
        if (!$model) {
            Log::error("LLM model not found: {$modelSlug}");
            return null;
        }

        // Si API direct (Claude Opus)
        if ($model->type === 'api') {
            return $this->executeApiInference($model, $prompt, $options);
        }

        // Sinon, créer un job pour le cluster local
        $job = LlmJob::create([
            'llm_model_id' => $model->id,
            'user_id' => auth()->id() ?? null,
            'status' => 'queued',
            'prompt' => $prompt,
            'metadata' => array_merge([
                'temperature' => 0.7,
                'max_tokens' => 2048,
                'top_p' => 0.9,
            ], $options),
            'queued_at' => now(),
        ]);

        // Essayer de dispatcher immédiatement
        $this->dispatchJob($job);

        return $job;
    }

    /**
     * Exécute l'inférence via API (Claude Opus fallback).
     */
    private function executeApiInference(LlmModel $model, string $prompt, array $options): ?LlmJob
    {
        $startTime = microtime(true);
        
        try {
            $config = $model->config;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json',
            ])->timeout(120)->post($config['endpoint'], [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => $options['max_tokens'] ?? 2048,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            $result = $response->json();
            $processingTimeMs = (int) ((microtime(true) - $startTime) * 1000);
            
            $content = $result['choices'][0]['message']['content'] ?? '';
            $inputTokens = $result['usage']['prompt_tokens'] ?? 0;
            $outputTokens = $result['usage']['completion_tokens'] ?? 0;

            return LlmJob::create([
                'llm_model_id' => $model->id,
                'user_id' => auth()->id() ?? null,
                'status' => 'completed',
                'prompt' => $prompt,
                'response' => $content,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'processing_time_ms' => $processingTimeMs,
                'metadata' => $options,
                'queued_at' => now(),
                'started_at' => now(),
                'completed_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('API inference failed', ['error' => $e->getMessage()]);
            
            return LlmJob::create([
                'llm_model_id' => $model->id,
                'user_id' => auth()->id() ?? null,
                'status' => 'failed',
                'prompt' => $prompt,
                'error_message' => $e->getMessage(),
                'queued_at' => now(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Distribue les jobs en attente sur le cluster.
     */
    public function dispatchPendingJobs(): int
    {
        $dispatched = 0;
        
        // Nettoyer les workers offline
        $this->markOfflineWorkers();
        
        // Récupérer les jobs en attente
        $pendingJobs = LlmJob::where('status', 'queued')
            ->orderBy('queued_at')
            ->limit(50)
            ->get();

        foreach ($pendingJobs as $job) {
            if ($this->dispatchJob($job)) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    /**
     * Dispatch un job sur un worker disponible.
     */
    public function dispatchJob(LlmJob $job): bool
    {
        $model = $job->model;
        
        if (!$model) {
            $job->markFailed('Model not found');
            return false;
        }

        // Trouver le meilleur worker
        $worker = $this->findBestWorker($model);
        
        if (!$worker) {
            Log::warning("No LLM worker available for model: {$model->slug}");
            
            // Fallback vers API si possible
            if ($model->type === 'hybrid' && $model->fallback_model) {
                return $this->fallbackToApi($job, $model);
            }
            
            return false;
        }

        try {
            $job->markProcessing($worker->id);
            $worker->markBusy();
            
            Log::info('LLM job dispatched', [
                'job_id' => $job->id,
                'model' => $model->slug,
                'worker_id' => $worker->id,
            ]);

            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch LLM job', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Fallback vers API.
     */
    private function fallbackToApi(LlmJob $job, LlmModel $model): bool
    {
        $fallback = $model->getFallbackModel();
        
        if (!$fallback || $fallback->type !== 'api') {
            return false;
        }

        Log::info('LLM fallback to API', [
            'job_id' => $job->id,
            'fallback' => $fallback->slug,
        ]);

        $result = $this->executeApiInference($fallback, $job->prompt, $job->metadata ?? []);
        
        if ($result && $result->status === 'completed') {
            $job->update([
                'status' => 'completed',
                'response' => $result->response,
                'input_tokens' => $result->input_tokens,
                'output_tokens' => $result->output_tokens,
                'processing_time_ms' => $result->processing_time_ms,
                'completed_at' => now(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Trouve le meilleur worker pour un modèle.
     */
    private function findBestWorker(LlmModel $model): ?LlmWorker
    {
        $workers = LlmWorker::whereIn('status', ['online', 'busy'])
            ->where('last_heartbeat', '>', now()->subMinutes(self::HEARTBEAT_TIMEOUT_MINUTES))
            ->with('audioWorker')
            ->get();

        // Filtrer ceux qui supportent le modèle
        $eligible = $workers->filter(function ($worker) use ($model) {
            return $worker->canRunModel($model->slug) && 
                   $worker->isAvailable();
        });

        if ($eligible->isEmpty()) {
            return null;
        }

        // Scorer et trier
        return $eligible
            ->sortByDesc(function ($worker) {
                $score = 0;
                
                // Bonus GPU
                if ($worker->audioWorker && $worker->audioWorker->has_gpu) {
                    $score += 1000;
                }
                
                // Bonus performance historique
                if ($worker->avg_tokens_per_second > 0) {
                    $score += $worker->avg_tokens_per_second * 10;
                }
                
                // Pénalité si déjà occupé
                if ($worker->status === 'busy') {
                    $score -= 500;
                }

                return $score;
            })
            ->first();
    }

    /**
     * Marque les workers offline.
     */
    public function markOfflineWorkers(): int
    {
        $offline = LlmWorker::whereIn('status', ['online', 'busy'])
            ->where('last_heartbeat', '<', now()->subMinutes(self::HEARTBEAT_TIMEOUT_MINUTES))
            ->get();

        foreach ($offline as $worker) {
            $worker->update(['status' => 'offline']);
            
            // Libérer les jobs
            LlmJob::where('llm_worker_id', $worker->id)
                ->where('status', 'processing')
                ->update([
                    'status' => 'queued',
                    'llm_worker_id' => null,
                    'started_at' => null,
                ]);
        }

        return $offline->count();
    }

    /**
     * Récupère les statistiques du cluster.
     */
    public function getStats(): array
    {
        $totalWorkers = LlmWorker::count();
        $onlineWorkers = LlmWorker::where('status', 'online')->count();
        $busyWorkers = LlmWorker::where('status', 'busy')->count();
        $gpuWorkers = LlmWorker::whereHas('audioWorker', function ($q) {
            $q->where('has_gpu', true);
        })->whereIn('status', ['online', 'busy'])->count();

        $totalTokens = LlmJob::where('status', 'completed')->sum('input_tokens') + 
                       LlmJob::where('status', 'completed')->sum('output_tokens');
        
        $todayTokens = LlmJob::where('status', 'completed')
            ->whereDate('completed_at', today())
            ->sum('input_tokens') + 
            LlmJob::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('output_tokens');

        return [
            'workers' => [
                'total' => $totalWorkers,
                'online' => $onlineWorkers,
                'busy' => $busyWorkers,
                'gpu_enabled' => $gpuWorkers,
            ],
            'jobs' => [
                'queued' => LlmJob::where('status', 'queued')->count(),
                'processing' => LlmJob::where('status', 'processing')->count(),
                'completed_today' => LlmJob::where('status', 'completed')
                    ->whereDate('completed_at', today())
                    ->count(),
                'failed_today' => LlmJob::where('status', 'failed')
                    ->whereDate('completed_at', today())
                    ->count(),
            ],
            'performance' => [
                'total_tokens' => (int) $totalTokens,
                'today_tokens' => (int) $todayTokens,
                'avg_tokens_per_second' => round(LlmWorker::avg('avg_tokens_per_second') ?? 0, 2),
            ],
        ];
    }
}