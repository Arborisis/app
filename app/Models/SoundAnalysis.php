<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoundAnalysis extends Model
{
    use HasFactory;

    protected $table = 'sound_analyses';

    protected $fillable = [
        'sound_id',
        'status',
        'original_r2_key',
        'features_json',
        'features_detailed_json',
        'parameters_json',
        'processed_at',
        'failed_reason',
        'error_message',
        'attempts',
        'queued_at',
        'started_at',
        'completed_at',
        'failed_at',
        'duration_seconds',
        'sample_rate',
        'channels',
        'bitrate',
        'format',
        'loudness_lufs',
        'peak_db',
        'rms_db',
        'noise_floor_db',
        'spectral_centroid',
        'spectral_rolloff',
        'zero_crossing_rate',
        'waveform_r2_key',
        'spectrogram_r2_key',
        'features_r2_key',
        'birdnet_r2_key',
        'summary_r2_key',
        'preview_r2_key',
        'quality_label',
        'quality_json',
        'birdnet_raw_json',
        'acoustic_event_count',
        'acoustic_diversity_index',
    ];

    protected $casts = [
        'status' => AnalysisStatus::class,
        'features_json' => 'array',
        'features_detailed_json' => 'array',
        'parameters_json' => 'array',
        'processed_at' => 'datetime',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'duration_seconds' => 'float',
        'loudness_lufs' => 'float',
        'peak_db' => 'float',
        'rms_db' => 'float',
        'noise_floor_db' => 'float',
        'spectral_centroid' => 'float',
        'spectral_rolloff' => 'float',
        'zero_crossing_rate' => 'float',
        'quality_json' => 'array',
        'attempts' => 'integer',
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function visualizations(): HasMany
    {
        return $this->hasMany(SoundVisualization::class);
    }

    public function birdnetDetections(): HasMany
    {
        return $this->hasMany(BirdnetDetection::class);
    }

    public function markQueued(): void
    {
        $this->update([
            'status' => AnalysisStatus::QUEUED,
            'queued_at' => now(),
            'failed_reason' => null,
            'error_message' => null,
        ]);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => AnalysisStatus::PROCESSING,
            'started_at' => now(),
            'failed_reason' => null,
            'error_message' => null,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status' => AnalysisStatus::COMPLETED,
            'completed_at' => now(),
            'processed_at' => now(),
            'failed_reason' => null,
            'error_message' => null,
        ]);
    }

    public function markFailed(string $reason, ?string $detailedError = null): void
    {
        $this->update([
            'status' => AnalysisStatus::FAILED,
            'failed_at' => now(),
            'processed_at' => now(),
            'failed_reason' => $reason,
            'error_message' => $detailedError ?? $reason,
        ]);
    }

    public function scopeForSound($query, Sound $sound)
    {
        return $query->where('sound_id', $sound->id);
    }
}
