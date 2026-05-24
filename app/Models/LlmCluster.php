<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LlmModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'description', 'type', 'status',
        'requirements', 'config', 'fallback_model', 'is_default'
    ];

    protected $casts = [
        'requirements' => 'array',
        'config' => 'array',
        'is_default' => 'boolean',
    ];

    public function jobs(): HasMany
    {
        return $this->hasMany(LlmJob::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function getFallbackModel(): ?self
    {
        if ($this->fallback_model) {
            return self::where('slug', $this->fallback_model)->first();
        }
        return null;
    }
}

class LlmWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'audio_worker_id', 'status', 'capabilities',
        'current_tokens_processed', 'total_tokens_processed',
        'total_requests', 'avg_tokens_per_second', 'last_heartbeat'
    ];

    protected $casts = [
        'capabilities' => 'array',
        'avg_tokens_per_second' => 'float',
        'last_heartbeat' => 'datetime',
    ];

    public function audioWorker(): BelongsTo
    {
        return $this->belongsTo(AudioWorker::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(LlmJob::class);
    }

    public function markOnline(): void
    {
        $this->update(['status' => 'online', 'last_heartbeat' => now()]);
    }

    public function markBusy(): void
    {
        $this->update(['status' => 'busy', 'last_heartbeat' => now()]);
    }

    public function isAvailable(): bool
    {
        return in_array($this->status, ['online', 'busy']) 
            && $this->last_heartbeat > now()->subMinutes(5);
    }

    public function canRunModel(string $modelSlug): bool
    {
        return in_array($modelSlug, $this->capabilities ?? []);
    }

    public function updateStats(int $inputTokens, int $outputTokens, float $processingTimeMs): void
    {
        $totalTokens = $inputTokens + $outputTokens;
        $tokensPerSec = $processingTimeMs > 0 ? ($totalTokens / ($processingTimeMs / 1000)) : 0;
        
        $this->update([
            'total_tokens_processed' => $this->total_tokens_processed + $totalTokens,
            'total_requests' => $this->total_requests + 1,
            'avg_tokens_per_second' => (($this->avg_tokens_per_second * $this->total_requests) + $tokensPerSec) / ($this->total_requests + 1),
        ]);
    }
}

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