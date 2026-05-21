<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoundWalk extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'visibility_status',
        'moderation_status',
        'route_geometry',
        'start_latitude',
        'start_longitude',
        'approximate_start_latitude',
        'approximate_start_longitude',
        'estimated_duration_minutes',
        'difficulty_level',
        'tags',
        'cover_image',
        'audio_environment_type',
        'approved_at',
        'approved_by',
    ];

    protected $hidden = [
        'start_latitude',
        'start_longitude',
    ];

    public function casts(): array
    {
        return [
            'start_latitude' => 'decimal:8',
            'start_longitude' => 'decimal:8',
            'approximate_start_latitude' => 'decimal:8',
            'approximate_start_longitude' => 'decimal:8',
            'route_geometry' => 'array',
            'tags' => 'array',
            'difficulty_level' => 'integer',
            'estimated_duration_minutes' => 'integer',
            'moderation_status' => ModerationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function points(): HasMany
    {
        return $this->hasMany(SoundWalkPoint::class)->orderBy('order');
    }

    public function scopeApproved($query)
    {
        return $query->where('moderation_status', ModerationStatus::Approved);
    }

    public function scopePublic($query)
    {
        return $query->approved()->where('visibility_status', 'public');
    }

    public function scopePending($query)
    {
        return $query->where('moderation_status', ModerationStatus::Pending);
    }

    public function isApproved(): bool
    {
        return $this->moderation_status === ModerationStatus::Approved;
    }

    public function isPubliclyVisible(): bool
    {
        return $this->isApproved() && $this->visibility_status === 'public';
    }

    public function getPublicStartLatitude(): float
    {
        return (float) $this->approximate_start_latitude;
    }

    public function getPublicStartLongitude(): float
    {
        return (float) $this->approximate_start_longitude;
    }

    public static function publicCoordinates(
        float $latitude,
        float $longitude,
        NatureSensitivityLevel|string|null $sensitivityLevel = null,
    ): array {
        $sensitivityLevel = $sensitivityLevel instanceof NatureSensitivityLevel
            ? $sensitivityLevel
            : NatureSensitivityLevel::tryFrom((string) ($sensitivityLevel ?? NatureSensitivityLevel::Normal->value));

        if (! $sensitivityLevel?->requiresApproximateLocation()) {
            return [
                'approximate_latitude' => round($latitude, 8),
                'approximate_longitude' => round($longitude, 8),
            ];
        }

        return [
            'approximate_latitude' => round($latitude, 2),
            'approximate_longitude' => round($longitude, 2),
        ];
    }
}
