<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ListeningPoint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_user_id',
        'title',
        'slug',
        'description',
        'exact_latitude',
        'exact_longitude',
        'public_latitude',
        'public_longitude',
        'public_accuracy_meters',
        'environment_id',
        'habitat_type',
        'country_code',
        'admin_level_1',
        'elevation_meters',
        'moderation_status',
        'nature_sensitivity_level',
        'approved_at',
        'approved_by',
        'recordings_count',
        'species_detected_count',
        'first_recorded_at',
        'last_recorded_at',
        'dominant_tags',
        'stats_cache',
    ];

    protected $hidden = [
        'exact_latitude',
        'exact_longitude',
    ];

    public function casts(): array
    {
        return [
            'exact_latitude' => 'decimal:8',
            'exact_longitude' => 'decimal:8',
            'public_latitude' => 'decimal:8',
            'public_longitude' => 'decimal:8',
            'public_accuracy_meters' => 'integer',
            'recordings_count' => 'integer',
            'species_detected_count' => 'integer',
            'first_recorded_at' => 'datetime',
            'last_recorded_at' => 'datetime',
            'dominant_tags' => 'array',
            'stats_cache' => 'array',
            'moderation_status' => ModerationStatus::class,
            'nature_sensitivity_level' => NatureSensitivityLevel::class,
            'approved_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ListeningPoint $point) {
            if (empty($point->slug)) {
                $point->slug = Str::slug($point->title.'-'.uniqid());
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function soundLocations(): HasMany
    {
        return $this->hasMany(SoundLocation::class);
    }

    public function sounds(): HasMany
    {
        return $this->hasMany(Sound::class);
    }

    public function environmentalObservations(): HasMany
    {
        return $this->hasMany(EnvironmentalObservation::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ListeningPointSnapshot::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ListeningPointVersion::class)->orderByDesc('version_number');
    }

    public function latestVersion(): HasMany
    {
        return $this->hasMany(ListeningPointVersion::class)->latest('version_number')->limit(1);
    }

    public function scientificMetrics(): MorphMany
    {
        return $this->morphMany(ScientificMetric::class, 'measurable');
    }

    public function userContributions(): HasMany
    {
        return $this->hasMany(UserContribution::class);
    }

    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_contributions')
            ->withPivot(['recordings_count', 'species_found_count', 'total_duration_seconds', 'first_contribution_at', 'last_contribution_at'])
            ->withTimestamps();
    }

    public function scopeApproved($query)
    {
        return $query->where('moderation_status', ModerationStatus::Approved);
    }

    public function scopePubliclyVisible($query)
    {
        return $query->approved()
            ->whereHas('sounds', fn ($q) => $q->public()->where('status', 'published'));
    }

    public function isApproved(): bool
    {
        return $this->moderation_status === ModerationStatus::Approved;
    }

    public function getPublicCoordinates(): array
    {
        return [
            'latitude' => (float) $this->public_latitude,
            'longitude' => (float) $this->public_longitude,
            'accuracy_meters' => $this->public_accuracy_meters,
        ];
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
                'public_latitude' => round($latitude, 8),
                'public_longitude' => round($longitude, 8),
                'public_accuracy_meters' => 100,
            ];
        }

        return self::obscure($latitude, $longitude);
    }

    public static function obscure(float $latitude, float $longitude): array
    {
        return [
            'public_latitude' => round($latitude, 2),
            'public_longitude' => round($longitude, 2),
            'public_accuracy_meters' => 1000,
        ];
    }
}
