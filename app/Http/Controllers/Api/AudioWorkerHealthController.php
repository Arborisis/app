<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AudioWorker;
use App\Models\AudioWorkerAssignment;
use App\Services\AudioWorkerDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AudioWorkerHealthController extends Controller
{
    public function __construct(
        private AudioWorkerDispatchService $dispatchService
    ) {}

    public function status(): JsonResponse
    {
        $stats = $this->dispatchService->getWorkerStats();
        $queueMetrics = $this->dispatchService->getQueueMetrics();
        
        // Déterminer le statut global
        $status = 'healthy';
        $issues = [];
        
        if ($stats['online_workers'] === 0 && $stats['pending_jobs'] > 0) {
            $status = 'warning';
            $issues[] = 'No workers online but jobs pending';
        }
        
        if ($queueMetrics['oldest_pending_minutes'] > 60) {
            $status = 'warning';
            $issues[] = 'Jobs waiting for more than 1 hour';
        }
        
        if ($stats['failed_today'] > $stats['completed_today'] * 0.1) {
            $status = 'critical';
            $issues[] = 'High failure rate (>10%)';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'workers' => $stats,
            'queue' => $queueMetrics,
            'issues' => $issues,
        ]);
    }

    public function workerStatus(Request $request): JsonResponse
    {
        $worker = AudioWorker::where('token', $request->bearerToken())
            ->first();

        if (!$worker) {
            return response()->json(['error' => 'Worker not found'], 404);
        }

        $activeAssignments = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
            ->whereIn('status', ['assigned', 'processing'])
            ->with('soundAnalysis')
            ->get();

        $recentAssignments = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
            ->whereIn('status', ['completed', 'failed'])
            ->whereDate('completed_at', today())
            ->orderByDesc('completed_at')
            ->limit(10)
            ->get();

        return response()->json([
            'worker' => [
                'id' => $worker->id,
                'name' => $worker->name,
                'status' => $worker->status,
                'capabilities' => [
                    'cpu_cores' => $worker->cpu_cores,
                    'memory_gb' => $worker->memory_gb,
                    'has_gpu' => $worker->has_gpu,
                ],
                'performance' => [
                    'total_completed' => $worker->total_jobs_completed,
                    'total_failed' => $worker->total_jobs_failed,
                    'avg_processing_time' => $worker->avg_processing_time,
                ],
                'last_seen' => $worker->last_seen_at,
            ],
            'active_jobs' => $activeAssignments->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    'analysis_id' => $assignment->sound_analysis_id,
                    'status' => $assignment->status,
                    'assigned_at' => $assignment->assigned_at,
                    'started_at' => $assignment->started_at,
                ];
            }),
            'recent_jobs' => $recentAssignments->map(function ($assignment) {
                return [
                    'assignment_id' => $assignment->id,
                    'status' => $assignment->status,
                    'processing_time' => $assignment->processing_time_seconds,
                    'completed_at' => $assignment->completed_at,
                ];
            }),
        ]);
    }

    public function queueStatus(): JsonResponse
    {
        $metrics = $this->dispatchService->getQueueMetrics();
        
        // Historique des 24 dernières heures (par heure)
        $hourlyStats = Cache::remember('worker_hourly_stats', 300, function () {
            $stats = [];
            
            for ($i = 23; $i >= 0; $i--) {
                $hour = now()->subHours($i);
                $stats[] = [
                    'hour' => $hour->format('H:00'),
                    'completed' => AudioWorkerAssignment::where('status', 'completed')
                        ->whereHour('completed_at', $hour->hour)
                        ->whereDate('completed_at', $hour->toDateString())
                        ->count(),
                    'failed' => AudioWorkerAssignment::where('status', 'failed')
                        ->whereHour('completed_at', $hour->hour)
                        ->whereDate('completed_at', $hour->toDateString())
                        ->count(),
                ];
            }
            
            return $stats;
        });

        return response()->json([
            'current' => $metrics,
            'hourly_history' => $hourlyStats,
            'workers_by_status' => [
                'online' => AudioWorker::where('status', 'online')->count(),
                'busy' => AudioWorker::where('status', 'busy')->count(),
                'offline' => AudioWorker::where('status', 'offline')->count(),
            ],
        ]);
    }
}