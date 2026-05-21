<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupRecordingEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'title',
        'description',
        'latitude',
        'longitude',
        'scheduled_at',
        'event_type',
        'max_participants',
        'status',
    ];

    public function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'scheduled_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GroupRecordingParticipant::class, 'event_id');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')->where('scheduled_at', '>', now());
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        // Rough bounding box filter — precise distance can be done in app layer
        $latDelta = $radiusKm / 111;
        $lngDelta = $radiusKm / (111 * cos(deg2rad($lat)));

        return $query
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta]);
    }
}
