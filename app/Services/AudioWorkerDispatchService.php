<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AnalysisStatus;
use App\Models\AudioWorker;
use App\Models\AudioWorkerAssignment;
use App\Models\SoundAnalysis;
use Illuminate\Support\Facades\Log;

class AudioWorkerDispatchService
{
    public function dispatchPendingJobs(): void
    {
        $pendingAnalyses = SoundAnalysis::where('status', AnalysisStatus::QUEUED)
            ->whereDoesntHave('workerAssignments', function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            })
            ->orderBy('queued_at')
            ->get();

        foreach ($pendingAnalyses as $analysis) {
            $this->dispatchJob($analysis);
        }
    }

    public function dispatchJob(SoundAnalysis $analysis): ?AudioWorkerAssignment
    {
        $bestWorker = $this->findBestWorker($analysis);

        if (!$bestWorker) {
            Log::warning('No available worker for analysis', ['analysis_id' => $analysis->id]);
            return null;
        }

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
        ]);

        return $assignment;
    }

    public function assignNextJob(AudioWorker $worker): ?AudioWorkerAssignment
    {
        $existingAssignment = AudioWorkerAssignment::where('audio_worker_id', $worker->id)
            ->where('status', 'assigned')
            ->first();

        if ($existingAssignment) {
            return $existingAssignment;
        }

        $pendingAnalysis = SoundAnalysis::where('status', AnalysisStatus::QUEUED)
            ->whereDoesntHave('workerAssignments', function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            })
            ->orderBy('queued_at')
            ->first();

        if (!$pendingAnalysis) {
            return null;
        }

        $assignment = AudioWorkerAssignment::create([
            'audio_worker_id' => $worker->id,
            'sound_analysis_id' => $pendingAnalysis->id,
            'status' => 'assigned',
            'assigned_at' => now(),
        ]);

        $pendingAnalysis->markProcessing();

        return $assignment;
    }

    private function findBestWorker(SoundAnalysis $analysis): ?AudioWorker
    {
        $workers = AudioWorker::available()
            ->withCount(['assignments' => function ($query) {
                $query->whereIn('status', ['assigned', 'processing']);
            }])
            ->having('assignments_count', '<', 3)
            ->get();

        if ($workers->isEmpty()) {
            return null;
        }

        return $workers
            ->sortByDesc(function ($worker) {
                $score = $worker->getCapabilityScore();
                
                $loadPenalty = $worker->assignments_count * 200;
                $score -= $loadPenalty;
                
                if ($worker->avg_processing_time > 0) {
                    $speedBonus = max(0, 300 - $worker->avg_processing_time);
                    $score += $speedBonus;
                }
                
                if ($worker->total_jobs_failed > $worker->total_jobs_completed) {
                    $score -= 1000;
                }

                return $score;
            })
            ->first();
    }

    public function reassignTimeoutJobs(): void
    {
        $timeoutAssignments = AudioWorkerAssignment::where('status', 'processing')
            ->where('started_at', '<', now()->subMinutes(30))
            ->get();

        foreach ($timeoutAssignments as $assignment) {
            $assignment->markTimeout();
            
            $analysis = $assignment->soundAnalysis;
            if ($analysis) {
                $analysis->update([
                    'status' => AnalysisStatus::QUEUED,
                    'started_at' => null,
                ]);
            }

            $worker = $assignment->audioWorker;
            if ($worker) {
                $worker->markOnline();
            }

            Log::info('Job reassigned after timeout', [
                'assignment_id' => $assignment->id,
                'analysis_id' => $analysis?->id,
            ]);

            if ($analysis) {
                $this->dispatchJob($analysis);
            }
        }
    }

    public function getWorkerStats(): array
    {
        return [
            'total_workers' => AudioWorker::count(),
            'online_workers' => AudioWorker::where('status', 'online')->count(),
            'busy_workers' => AudioWorker::where('status', 'busy')->count(),
            'offline_workers' => AudioWorker::where('status', 'offline')->count(),
            'pending_jobs' => SoundAnalysis::where('status', AnalysisStatus::QUEUED)->count(),
            'processing_jobs' => AudioWorkerAssignment::where('status', 'processing')->count(),
            'completed_today' => AudioWorkerAssignment::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
        ];
    }
}