<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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