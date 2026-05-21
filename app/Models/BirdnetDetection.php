<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $sound_analysis_id
 * @property int $sound_id
 * @property string $scientific_name
 * @property string $common_name
 * @property float $confidence
 * @property float $start_time
 * @property float $end_time
 * @property float|null $frequency_min
 * @property float|null $frequency_max
 * @property string $source
 * @property bool $is_validated
 * @property int|null $validated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 *
 * @property-read SoundAnalysis $soundAnalysis
 * @property-read Sound $sound
 * @property-read User|null $validatedBy
 *
 * @method static Builder<static>|BirdnetDetection newModelQuery()
 * @method static Builder<static>|BirdnetDetection newQuery()
 * @method static Builder<static>|BirdnetDetection query()
 * @method static Builder<static>|BirdnetDetection forSound(int $soundId)
 * @method static Builder<static>|BirdnetDetection forAnalysis(int $analysisId)
 * @method static Builder<static>|BirdnetDetection confident(float $minConfidence = 0.25)
 * @method static Builder<static>|BirdnetDetection validated()
 * @method static Builder<static>|BirdnetDetection pending()
 * @method static Builder<static>|BirdnetDetection byConfidence(string $direction = 'desc')
 * @method static Builder<static>|BirdnetDetection inTimeRange(float $from, float $to)
 * @method static Builder<static>|BirdnetDetection species(string $scientificName)
 */
class BirdnetDetection extends Model
{
    /** @use HasFactory<\Database\Factories\BirdnetDetectionFactory> */
    use HasFactory;

    protected $table = 'birdnet_detections';

    protected $primaryKey = 'id';

    protected $keyType = 'int';

    public $incrementing = true;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sound_analysis_id',
        'sound_id',
        'scientific_name',
        'common_name',
        'confidence',
        'start_time',
        'end_time',
        'frequency_min',
        'frequency_max',
        'source',
        'is_validated',
        'validated_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'confidence' => 'float',
        'start_time' => 'float',
        'end_time' => 'float',
        'frequency_min' => 'float',
        'frequency_max' => 'float',
        'is_validated' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function soundAnalysis(): BelongsTo
    {
        return $this->belongsTo(SoundAnalysis::class);
    }

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to detections belonging to a specific sound.
     */
    public function scopeForSound(Builder $query, int $soundId): Builder
    {
        return $query->where('sound_id', $soundId);
    }

    /**
     * Scope to detections belonging to a specific analysis.
     */
    public function scopeForAnalysis(Builder $query, int $analysisId): Builder
    {
        return $query->where('sound_analysis_id', $analysisId);
    }

    /**
     * Scope to detections with confidence above a threshold.
     */
    public function scopeConfident(Builder $query, float $minConfidence = 0.25): Builder
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    /**
     * Scope to validated detections only.
     */
    public function scopeValidated(Builder $query): Builder
    {
        return $query->where('is_validated', true);
    }

    /**
     * Scope to non-validated (pending) detections.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_validated', false);
    }

    /**
     * Order by confidence.
     */
    public function scopeByConfidence(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('confidence', $direction);
    }

    /**
     * Scope to detections overlapping a given time range.
     */
    public function scopeInTimeRange(Builder $query, float $from, float $to): Builder
    {
        return $query->where(function (Builder $q) use ($from, $to): void {
            $q->whereBetween('start_time', [$from, $to])
                ->orWhereBetween('end_time', [$from, $to])
                ->orWhere(function (Builder $inner) use ($from, $to): void {
                    $inner->where('start_time', '<=', $from)
                        ->where('end_time', '>=', $to);
                });
        });
    }

    /**
     * Scope to a specific species.
     */
    public function scopeSpecies(Builder $query, string $scientificName): Builder
    {
        return $query->where('scientific_name', $scientificName);
    }

    // -------------------------------------------------------------------------
    // Computed attributes
    // -------------------------------------------------------------------------

    /**
     * Duration in seconds.
     */
    public function getDurationAttribute(): float
    {
        return max(0.0, $this->end_time - $this->start_time);
    }

    /**
     * Frequency bandwidth in Hz.
     */
    public function getBandwidthAttribute(): ?float
    {
        if ($this->frequency_min === null || $this->frequency_max === null) {
            return null;
        }

        return max(0.0, $this->frequency_max - $this->frequency_min);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if this detection overlaps with another one.
     */
    public function overlaps(self $other, float $threshold = 0.5): bool
    {
        if ($this->scientific_name !== $other->scientific_name) {
            return false;
        }

        $startMax = max($this->start_time, $other->start_time);
        $endMin = min($this->end_time, $other->end_time);
        $overlap = max(0.0, $endMin - $startMax);

        $durationA = $this->duration;
        $durationB = $other->duration;

        if ($durationA <= 0.0 || $durationB <= 0.0) {
            return false;
        }

        $iou = $overlap / min($durationA, $durationB);

        return $iou >= $threshold;
    }

    /**
     * Mark this detection as validated.
     */
    public function validate(int $validatedBy): bool
    {
        return $this->update([
            'is_validated' => true,
            'validated_by' => $validatedBy,
        ]);
    }

    /**
     * Revoke validation.
     */
    public function revokeValidation(): bool
    {
        return $this->update([
            'is_validated' => false,
            'validated_by' => null,
        ]);
    }
}
