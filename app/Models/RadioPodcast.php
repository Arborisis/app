<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RadioPodcast extends Model
{
    use HasFactory;

    protected $fillable = [
        'show_type',
        'status',
        'title',
        'description',
        'theme',
        'script_json',
        'research_json',
        'context_json',
        'tts_text',
        'voice_provider',
        'voice_id',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'target_duration_seconds',
        'actual_duration_seconds',
        'sound_ids',
        'generation_cost_cents',
        'host_personality_slug',
        'production_preset',
        'channel_id',
        'idempotency_key',
        'error_message',
        'scheduled_at',
        'published_at',
        'failed_at',
    ];

    protected $casts = [
        'show_type'    => RadioShowType::class,
        'production_preset' => RadioProductionPreset::class,
        'status' => RadioPodcastStatus::class,
        'script_json' => 'array',
        'research_json' => 'array',
        'context_json' => 'array',
        'sound_ids' => 'array',
        'size_bytes' => 'integer',
        'target_duration_seconds' => 'integer',
        'actual_duration_seconds' => 'float',
        'generation_cost_cents' => 'integer',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', RadioPodcastStatus::Published);
    }

    public function scopeReadyForAir(Builder $query): Builder
    {
        return $query->where('status', RadioPodcastStatus::Published)
            ->whereNotNull('path')
            ->whereNotNull('actual_duration_seconds');
    }

    public function sounds(): Builder
    {
        $ids = $this->sound_ids ?? [];

        return Sound::query()->whereIn('id', $ids);
    }
}
