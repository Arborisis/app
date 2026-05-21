<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadioGenerationJob extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    public const KIND_PODCAST = 'podcast';
    public const KIND_FLASH = 'flash';
    public const KIND_EMISSION = 'emission';
    public const KIND_DJ = 'dj';

    protected $fillable = [
        'kind',
        'idempotency_key',
        'status',
        'payload',
        'error_message',
        'attempts',
        'started_at',
        'completed_at',
        'channel_id',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(RadioChannel::class);
    }

    public function scopeFailedRecently(Builder $query, int $hours = 24): Builder
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('completed_at', '>=', now()->subHours($hours));
    }
}
