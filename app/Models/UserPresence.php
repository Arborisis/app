<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PresenceVisibilityMode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'approximate_latitude',
        'approximate_longitude',
        'last_seen_at',
        'visibility_mode',
        'expires_at',
    ];

    public function casts(): array
    {
        return [
            'approximate_latitude' => 'decimal:8',
            'approximate_longitude' => 'decimal:8',
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
            'visibility_mode' => PresenceVisibilityMode::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeVisible($query)
    {
        return $query->active()
            ->whereIn('visibility_mode', ['approximate', 'public_zone']);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
