<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Models\AudioWorker;
use App\Models\AudioWorkerAssignment;
use App\Models\SoundAnalysis;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AudioWorkerDispatchService
{
    private const MAX_ASSIGNMENT_ATTEMPTS = 3;
    private const TIMEOUT_MINUTES = 30;
    private const HEARTBEAT_TIMEOUT_MINUTES = 30;

    public function dispatchPendingJobs(): int
    {
        $dispatched = 0;
        
        // 1. D'abord réassigner les jobs en timeout
        $this->reassignTimeoutJobs();
        
        // 2. Nettoyer les workers offline
        $this->markOfflineWorkers();
        
        // 3. Dispatcher les jobs en attente
        $pendingAnalyses = SoundAnalysis::where('status', AnalysisStatus::QUEUED)
            ->whereDoesntHave('workerAssignments', function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            })
            ->orderBy('queued_at')
            ->limit(50)  // Traiter par lots de 50
            ->get();

        foreach ($pendingAnalyses as $analysis) {
            if ($this->dispatchJob($analysis)) {
                $dispatched++;
            }
        }

        if ($dispatched > 0) {
            Log::info('Jobs dispatched', ['count' => $dispatched]);
        }

        return $dispatched;
    }

    public function dispatchJob(SoundAnalysis $analysis): ?AudioWorkerAssignment
    {
        $bestWorker = $this->findBestWorker($analysis);

        if (!$bestWorker) {
            Log::warning('No available worker for analysis', [
                'analysis_id' => $analysis->id,
                'sound_id' => $analysis->sound_id,
            ]);
            return null;
        }

        try {
            $assignment = AudioWorkerAssignment::create([
                'audio_worker_id' => $bestWorker->id,
                'sound_analysis_id' => $analysis->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $analysis->markProcessing();
            $bestWorker->markBusy();

            Log::info('Job dispatched', [
                'analysis_id' => $analysis->id,
                'worker_id' => $bestWorker->id,
                'worker_name' => $bestWorker->name,
                'sound_id' => $analysis->sound_id,
            ]);

            return $assignment;
            
        } catch (\Exception $e) {
            Log::error('Failed to dispatch job', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function assignNextJob(AudioWorker $worker): ?AudioWorkerAssignment
    {
        // Vérifier si le worker a déjà un job assigné
        $existingAssignment = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
            ->where('status', 'assigned')
            ->first();

        if ($existingAssignment) {
            return $existingAssignment;
        }

        // Chercher le prochain job en attente
        $pendingAnalysis = SoundAnalysis::where('status', AnalysisStatus::QUEUED)
            ->whereDoesntHave('workerAssignments', function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            })
            ->orderBy('queued_at')
            ->first();

        if (!$pendingAnalysis) {
            return null;
        }

        try {
            $assignment = AudioWorkerAssignment::create([
                'audio_worker_id' => $worker->id,
                'sound_analysis_id' => $pendingAnalysis->id,
                'status' => 'assigned',
                'assigned_at' => now(),
            ]);

            $pendingAnalysis->markProcessing();

            return $assignment;
            
        } catch (\Exception $e) {
            Log::error('Failed to assign job to worker', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function findBestWorker(SoundAnalysis $analysis): ?AudioWorker
    {
        // Récupérer les workers disponibles avec leur charge actuelle
        $workers = AudioWorker::available()
            ->withCount(['assignments' => function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            }])
            ->having('assignments_count', '<', self::MAX_ASSIGNMENT_ATTEMPTS)
            ->get();

        if ($workers->isEmpty()) {
            return null;
        }

        // Scorer et trier les workers
        return $workers
            ->sortByDesc(function ($worker) use ($analysis) {
                return $this->calculateWorkerScore($worker, $analysis);
            })
            ->first();
    }

    private function calculateWorkerScore(AudioWorker $worker, SoundAnalysis $analysis): float
    {
        $score = 0;

        // 1. Capacité brute (CPU, RAM, GPU)
        $score += $worker->getCapabilityScore();

        // 2. Pénalité de charge
        $loadPenalty = $worker->assignments_count * 150;
        $score -= $loadPenalty;

        // 3. Bonus de vitesse historique
        if ($worker->avg_processing_time > 0) {
            $speedBonus = max(0, 300 - $worker->avg_processing_time);
            $score += $speedBonus;
        }

        // 4. Pénalité pour taux d'erreur élevé
        $totalJobs = $worker->total_jobs_completed + $worker->total_jobs_failed;
        if ($totalJobs > 5) {
            $errorRate = $worker->total_jobs_failed / $totalJobs;
            $score -= $errorRate * 500;
        }

        // 5. Bonus pour workers rapides récemment
        if ($worker->last_seen_at && $worker->last_seen_at->gt(now()->subMinutes(2))) {
            $score += 50;  // Worker très actif
        }

        // 6. Pénalité pour jobs récents échoués
        $recentFailures = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
            ->where('status', 'failed')
            ->where('completed_at', '>', now()->subMinutes(10))
            ->count();
        $score -= $recentFailures * 100;

        return $score;
    }

    public function reassignTimeoutJobs(): int
    {
        $timeoutAssignments = AudioWorkerAssignment::where('status', 'processing')
            ->where('started_at', '<', now()->subMinutes(self::TIMEOUT_MINUTES))
            ->get();

        $reassigned = 0;

        foreach ($timeoutAssignments as $assignment) {
            try {
                $assignment->markTimeout();
                
                $analysis = $assignment->soundAnalysis;
                $worker = $assignment->audioWorker;

                if ($analysis) {
                    $analysis->update([
                        'status' => AnalysisStatus::QUEUED,
                        'started_at' => null,
                    ]);
                }

                if ($worker) {
                    $worker->markOnline();
                }

                Log::warning('Job timeout, reassigned', [
                    'assignment_id' => $assignment->id,
                    'analysis_id' => $analysis?->id,
                    'worker_id' => $worker?->id,
                ]);

                $reassigned++;
                
            } catch (\Exception $e) {
                Log::error('Failed to reassign timeout job', [
                    'assignment_id' => $assignment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $reassigned;
    }

    public function markOfflineWorkers(): int
    {
        $offlineWorkers = AudioWorker::whereIn('status', ['online', 'busy'])
            ->where(function ($query) {
                $query->whereNull('last_seen_at')
                    ->orWhere('last_seen_at', '<', now()->subMinutes(self::HEARTBEAT_TIMEOUT_MINUTES));
            })
            ->get();

        $marked = 0;

        foreach ($offlineWorkers as $worker) {
            try {
                $worker->markOffline();
                
                // Libérer les jobs assignés à ce worker
                AudioWorkerAssignment::where('audio_worker_id', $worker->id)
                    ->whereIn('status', ['assigned', 'processing'])
                    ->update([
                        'status' => 'timeout',
                        'completed_at' => now(),
                    ]);

                // Remettre les analyses en queue
                $assignments = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
                    ->where('status', 'timeout')
                    ->get();

                foreach ($assignments as $assignment) {
                    if ($assignment->soundAnalysis) {
                        $assignment->soundAnalysis->update([
                            'status' => AnalysisStatus::QUEUED,
                            'started_at' => null,
                        ]);
                    }
                }

                Log::info('Worker marked offline', [
                    'worker_id' => $worker->id,
                    'worker_name' => $worker->name,
                ]);

                $marked++;
                
            } catch (\Exception $e) {
                Log::error('Failed to mark worker offline', [
                    'worker_id' => $worker->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $marked;
    }

    public function handleWorkerResult(AudioWorkerAssignment $assignment, string $status, 
                                     int $processingTime, ?array $results = null, 
                                     ?string $error = null): void
    {
        try {
            if ($status === 'completed') {
                $assignment->markCompleted($processingTime);
                
                $worker = $assignment->audioWorker;
                if ($worker) {
                    $worker->increment('total_jobs_completed');
                    $worker->update([
                        'avg_processing_time' => $this->calculateNewAverage(
                            $worker->avg_processing_time,
                            $worker->total_jobs_completed,
                            $processingTime
                        ),
                    ]);
                }

                $analysis = $assignment->soundAnalysis;
                if ($analysis) {
                    $analysis->markCompleted();
                    
                    // Mettre à jour les résultats si fournis
                    if ($results) {
                        $this->updateAnalysisResults($analysis, $results);
                    }
                }

                Log::info('Job completed', [
                    'assignment_id' => $assignment->id,
                    'processing_time' => $processingTime,
                ]);
                
            } else {
                $assignment->markFailed($error ?? 'Unknown error');
                
                $worker = $assignment->audioWorker;
                if ($worker) {
                    $worker->increment('total_jobs_failed');
                }

                $analysis = $assignment->soundAnalysis;
                if ($analysis) {
                    $analysis->markFailed('worker_error', $error);
                }

                Log::warning('Job failed', [
                    'assignment_id' => $assignment->id,
                    'error' => $error,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to handle worker result', [
                'assignment_id' => $assignment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function calculateNewAverage(float $currentAvg, int $count, int $newValue): float
    {
        if ($count <= 1) {
            return (float) $newValue;
        }
        
        return (($currentAvg * ($count - 1)) + $newValue) / $count;
    }

    private function updateAnalysisResults(SoundAnalysis $analysis, array $results): void
    {
        $updateData = [];

        if (isset($results['metadata'])) {
            $metadata = $results['metadata'];
            $updateData = array_merge($updateData, [
                'duration_seconds' => $metadata['duration_seconds'] ?? null,
                'sample_rate' => $metadata['sample_rate'] ?? null,
                'channels' => $metadata['channels'] ?? null,
                'bitrate' => $metadata['bitrate'] ?? null,
                'format' => $metadata['format'] ?? null,
            ]);
        }

        if (isset($results['features'])) {
            $features = $results['features'];
            $updateData['features_json'] = $features;
            
            // Extraire quelques features clés
            if (isset($features['temporal']['rms_mean'])) {
                $updateData['rms_db'] = $features['temporal']['rms_mean'];
            }
            if (isset($features['spectral']['centroid_mean'])) {
                $updateData['spectral_centroid'] = $features['spectral']['centroid_mean'];
            }
            if (isset($features['temporal']['zcr_mean'])) {
                $updateData['zero_crossing_rate'] = $features['temporal']['zcr_mean'];
            }
        }

        if (isset($results['files'])) {
            $files = $results['files'];
            if (isset($files['spectrogram'])) {
                $updateData['spectrogram_r2_key'] = $files['spectrogram'];
            }
            if (isset($files['birdnet'])) {
                $updateData['birdnet_r2_key'] = $files['birdnet'];
            }
            if (isset($files['preview'])) {
                $updateData['preview_r2_key'] = $files['preview'];
            }
        }

        if (isset($results['detections_count'])) {
            $updateData['acoustic_event_count'] = $results['detections_count'];
        }

        if (!empty($updateData)) {
            $analysis->update($updateData);
        }
    }

    public function getWorkerStats(): array
    {
        $totalWorkers = AudioWorker::count();
        $onlineWorkers = AudioWorker::where('status', 'online')->count();
        $busyWorkers = AudioWorker::where('status', 'busy')->count();
        
        return [
            'total_workers' => $totalWorkers,
            'online_workers' => $onlineWorkers,
            'busy_workers' => $busyWorkers,
            'offline_workers' => AudioWorker::where('status', 'offline')->count(),
            'pending_jobs' => SoundAnalysis::where('status', AnalysisStatus::QUEUED)->count(),
            'processing_jobs' => AudioWorkerAssignment::where('status', 'processing')->count(),
            'assigned_jobs' => AudioWorkerAssignment::where('status', 'assigned')->count(),
            'completed_today' => AudioWorkerAssignment::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'failed_today' => AudioWorkerAssignment::where('status', 'failed')
                ->whereDate('completed_at', today())
                ->count(),
            'avg_processing_time' => AudioWorkerAssignment::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->avg('processing_time_seconds') ?? 0,
            'capacity_usage_percent' => $totalWorkers > 0 
                ? round(($busyWorkers / $totalWorkers) * 100, 1) 
                : 0,
        ];
    }

    public function getQueueMetrics(): array
    {
        $now = now();
        
        return [
            'pending' => SoundAnalysis::where('status', AnalysisStatus::QUEUED)->count(),
            'processing' => AudioWorkerAssignment::where('status', 'processing')->count(),
            'assigned' => AudioWorkerAssignment::where('status', 'assigned')->count(),
            'oldest_pending_minutes' => SoundAnalysis::where('status', AnalysisStatus::QUEUED)
                ->orderBy('queued_at')
                ->value('queued_at')
                ?->diffInMinutes($now) ?? 0,
            'avg_wait_time_minutes' => SoundAnalysis::where('status', AnalysisStatus::QUEUED)
                ->whereNotNull('queued_at')
                ->avg('queued_at')
                ?->diffInMinutes($now) ?? 0,
        ];
    }
}