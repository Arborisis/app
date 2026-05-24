<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClusterTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'cluster_model_id',
        'audio_worker_id',
        'type',
        'status',
        'payload',
        'result',
        'processing_time_seconds',
        'error_message',
        'queued_at',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_time_seconds' => 'integer',
    ];

    public function clusterModel(): BelongsTo
    {
        return $this->belongsTo(ClusterModel::class);
    }

    public function audioWorker(): BelongsTo
    {
        return $this->belongsTo(AudioWorker::class);
    }

    public function markAssigned(int $workerId): void
    {
        $this->update([
            'audio_worker_id' => $workerId,
            'status' => 'assigned',
        ]);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $result, int $processingTime): void
    {
        $this->update([
            'status' => 'completed',
            'result' => $result,
            'processing_time_seconds' => $processingTime,
            'completed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'completed_at' => now(),
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'queued')->orderBy('queued_at');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeForModel($query, int $modelId)
    {
        return $query->where('cluster_model_id', $modelId);
    }
}