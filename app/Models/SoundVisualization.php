<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FrequencyScale;
use App\Enums\SpectrogramType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class SoundVisualization extends Model
{
    use HasFactory;

    protected $fillable = [
        'sound_analysis_id',
        'type',
        'scale',
        'file_path',
        'disk',
        'parameters_json',
    ];

    protected $casts = [
        'type' => SpectrogramType::class,
        'scale' => FrequencyScale::class,
        'parameters_json' => 'array',
    ];

    public function soundAnalysis(): BelongsTo
    {
        return $this->belongsTo(SoundAnalysis::class);
    }

    public function getUrlAttribute(): ?string
    {
        if (empty($this->file_path)) {
            return null;
        }

        if ($this->disk === 'r2') {
            return app(\App\Services\Storage\SignedUrlService::class)->url($this->disk, $this->file_path);
        }

        if ($this->disk === 'audio' || $this->disk === 's3') {
            return Storage::disk($this->disk)->temporaryUrl($this->file_path, now()->addMinutes(60));
        }

        return Storage::disk($this->disk)->url($this->file_path);
    }
}
