<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioListenerSessionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadioListenerSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_token',
        'channel_id',
        'user_id',
        'ip_hash',
        'ua_hash',
        'country',
        'started_at',
        'last_heartbeat_at',
        'ended_at',
        'bytes_streamed',
        'tracks_played',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'ended_at' => 'datetime',
        'bytes_streamed' => 'integer',
        'tracks_played' => 'integer',
        'status' => RadioListenerSessionStatus::class,
    ];

    public function channel(): BelongsTo
    {
        return $this->belongsTo(RadioChannel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', RadioListenerSessionStatus::Active);
    }

    public function scopeExpiredSince(Builder $query, int $ttlSeconds): Builder
    {
        return $query->where('status', RadioListenerSessionStatus::Active)
            ->where('last_heartbeat_at', '<', now()->subSeconds($ttlSeconds));
    }
}
