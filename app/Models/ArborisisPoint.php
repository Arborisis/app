<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ArborisisPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'description',
        'latitude',
        'longitude',
        'approximate_latitude',
        'approximate_longitude',
        'visibility_status',
        'moderation_status',
        'category',
        'tags',
        'difficulty_level',
        'nature_sensitivity_level',
        'recommended_time',
        'audio_environment_type',
        'cover_image',
        'approved_at',
        'approved_by',
    ];

    protected $hidden = [
        'latitude',
        'longitude',
    ];

    public function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'approximate_latitude' => 'decimal:8',
            'approximate_longitude' => 'decimal:8',
            'tags' => 'array',
            'difficulty_level' => 'integer',
            'category' => ArborisisCategory::class,
            'moderation_status' => ModerationStatus::class,
            'nature_sensitivity_level' => NatureSensitivityLevel::class,
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

    public function reports(): HasMany
    {
        return $this->hasMany(PointReport::class);
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(PointSuggestion::class);
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

    public function getPublicLatitude(): float
    {
        return (float) $this->approximate_latitude;
    }

    public function getPublicLongitude(): float
    {
        return (float) $this->approximate_longitude;
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

        return self::obscure($latitude, $longitude);
    }

    public static function obscure(float $latitude, float $longitude): array
    {
        return [
            'approximate_latitude' => round($latitude, 2),
            'approximate_longitude' => round($longitude, 2),
        ];
    }
}
