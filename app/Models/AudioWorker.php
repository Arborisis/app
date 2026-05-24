<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AudioWorker extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'hostname',
        'token',
        'status',
        'cpu_cores',
        'cpu_score',
        'memory_gb',
        'has_gpu',
        'gpu_model',
        'os',
        'ip_address',
        'port',
        'cloudflare_tunnel_id',
        'last_seen_at',
        'connected_at',
        'total_jobs_completed',
        'total_jobs_failed',
        'avg_processing_time',
        'capabilities',
        'error_message',
    ];

    protected $casts = [
        'has_gpu' => 'boolean',
        'last_seen_at' => 'datetime',
        'connected_at' => 'datetime',
        'capabilities' => 'array',
        'avg_processing_time' => 'float',
        'total_jobs_completed' => 'integer',
        'total_jobs_failed' => 'integer',
        'cpu_cores' => 'integer',
        'cpu_score' => 'integer',
        'memory_gb' => 'integer',
        'port' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(AudioWorkerAssignment::class);
    }

    public function markOnline(): void
    {
        $this->update([
            'status' => 'online',
            'last_seen_at' => now(),
        ]);
    }

    public function markOffline(): void
    {
        $this->update([
            'status' => 'offline',
        ]);
    }

    public function markBusy(): void
    {
        $this->update([
            'status' => 'busy',
            'last_seen_at' => now(),
        ]);
    }

    public function markError(string $message): void
    {
        $this->update([
            'status' => 'error',
            'error_message' => $message,
        ]);
    }

    public function isAvailable(): bool
    {
        return in_array($this->status, ['online', 'pending']) && $this->last_seen_at > now()->subMinutes(5);
    }

    public function getCapabilityScore(): int
    {
        $score = $this->cpu_cores * 100;
        $score += $this->memory_gb * 50;
        if ($this->has_gpu) {
            $score += 500;
        }
        if ($this->cpu_score) {
            $score += $this->cpu_score / 100;
        }
        return (int) $score;
    }

    public function scopeAvailable($query)
    {
        return $query->whereIn('status', ['online', 'pending'])
            ->where('last_seen_at', '>', now()->subMinutes(5));
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}