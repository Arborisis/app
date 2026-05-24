<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioWorkerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'audio_worker_id',
        'sound_analysis_id',
        'status',
        'assigned_at',
        'started_at',
        'completed_at',
        'processing_time_seconds',
        'error_message',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_time_seconds' => 'integer',
    ];

    public function audioWorker(): BelongsTo
    {
        return $this->belongsTo(AudioWorker::class);
    }

    public function soundAnalysis(): BelongsTo
    {
        return $this->belongsTo(SoundAnalysis::class);
    }

    public function markStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(int $processingTimeSeconds): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processing_time_seconds' => $processingTimeSeconds,
        ]);
    }

    public function markFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => $errorMessage,
        ]);
    }

    public function markTimeout(): void
    {
        $this->update([
            'status' => 'timeout',
            'completed_at' => now(),
        ]);
    }
}