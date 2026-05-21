<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningPointSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'listening_point_id',
        'period_type',
        'period_key',
        'recordings_count',
        'species_count',
        'species_list',
        'tags_distribution',
        'avg_duration_seconds',
        'avg_loudness_lufs',
        'avg_spectral_centroid',
        'biodiversity_score',
        'acoustic_activity_score',
        'acoustic_profile',
    ];

    protected $casts = [
        'recordings_count' => 'integer',
        'species_count' => 'integer',
        'species_list' => 'array',
        'tags_distribution' => 'array',
        'avg_duration_seconds' => 'decimal:2',
        'avg_loudness_lufs' => 'decimal:2',
        'avg_spectral_centroid' => 'decimal:2',
        'biodiversity_score' => 'decimal:3',
        'acoustic_activity_score' => 'decimal:3',
        'acoustic_profile' => 'array',
    ];

    public function listeningPoint(): BelongsTo
    {
        return $this->belongsTo(ListeningPoint::class);
    }
}
