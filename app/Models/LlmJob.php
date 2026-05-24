<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'llm_model_id', 'llm_worker_id', 'user_id', 'status',
        'prompt', 'response', 'input_tokens', 'output_tokens',
        'processing_time_ms', 'tokens_per_second', 'error_message',
        'metadata', 'queued_at', 'started_at', 'completed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_time_ms' => 'integer',
        'tokens_per_second' => 'float',
    ];

    public function model(): BelongsTo
    {
        return $this->belongsTo(LlmModel::class, 'llm_model_id');
    }

    public function worker(): BelongsTo
    {
        return $this->belongsTo(LlmWorker::class, 'llm_worker_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markProcessing(int $workerId): void
    {
        $this->update([
            'llm_worker_id' => $workerId,
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(string $response, int $inputTokens, int $outputTokens, float $processingTimeMs): void
    {
        $tokensPerSec = $processingTimeMs > 0 ? (($inputTokens + $outputTokens) / ($processingTimeMs / 1000)) : 0;
        
        $this->update([
            'status' => 'completed',
            'response' => $response,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'processing_time_ms' => $processingTimeMs,
            'tokens_per_second' => $tokensPerSec,
            'completed_at' => now(),
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
}