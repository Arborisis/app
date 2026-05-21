<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadioSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_name',
        'engine',
        'public_stream_url',
        'icecast_base_url',
        'icecast_mount',
        'tagline',
        'is_enabled',
        'shuffle_enabled',
        'loop_enabled',
        'dj_enabled',
        'dj_announcement_frequency',
        'dj_voice_provider',
        'dj_voice_id',
        'gap_ms',
        'crossfade_seconds',
        'icy_metaint',
        'history_limit',
        'max_listeners_display',
        'discord_voice_channel_id',
        'discord_auto_join',
        'listener_ttl_seconds',
        'podcast_enabled',
        'podcast_interval_tracks',
        'podcast_min_duration',
        'podcast_max_duration',
        'podcast_voice_id',
        'host_personalities_enabled',
        'host_continuity_enabled',
        'production_presets_enabled',
        'player_visualizer_enabled',
        'player_realtime_enabled',
        'monitoring_enabled',
        'multichannel_enabled',
        'discord_embeds_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'shuffle_enabled' => 'boolean',
        'loop_enabled' => 'boolean',
        'dj_enabled' => 'boolean',
        'dj_announcement_frequency' => 'integer',
        'gap_ms' => 'integer',
        'crossfade_seconds' => 'integer',
        'icy_metaint' => 'integer',
        'history_limit' => 'integer',
        'max_listeners_display' => 'integer',
        'discord_auto_join' => 'boolean',
        'listener_ttl_seconds' => 'integer',
        'podcast_enabled' => 'boolean',
        'podcast_interval_tracks' => 'integer',
        'podcast_min_duration' => 'integer',
        'podcast_max_duration' => 'integer',
        'host_personalities_enabled' => 'boolean',
        'host_continuity_enabled' => 'boolean',
        'production_presets_enabled' => 'boolean',
        'player_visualizer_enabled' => 'boolean',
        'player_realtime_enabled' => 'boolean',
        'monitoring_enabled' => 'boolean',
        'multichannel_enabled' => 'boolean',
        'discord_embeds_enabled' => 'boolean',
    ];

    /**
     * Helper exposing the feature flag state as a flat array.
     *
     * @return array<string, bool>
     */
    public function features(): array
    {
        return [
            'host_personalities' => (bool) $this->host_personalities_enabled,
            'host_continuity' => (bool) $this->host_continuity_enabled,
            'production_presets' => (bool) $this->production_presets_enabled,
            'player_visualizer' => (bool) $this->player_visualizer_enabled,
            'player_realtime' => (bool) $this->player_realtime_enabled,
            'monitoring' => (bool) $this->monitoring_enabled,
            'multichannel' => (bool) $this->multichannel_enabled,
            'discord_embeds' => (bool) $this->discord_embeds_enabled,
        ];
    }
}
