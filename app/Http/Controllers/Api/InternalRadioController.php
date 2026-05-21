<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Radio\UpdateNowPlayingRequest;
use App\Models\RadioSetting;
use App\Services\Radio\RadioPlaylistExportService;
use App\Services\Radio\RadioStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalRadioController extends Controller
{
    public function __construct(
        private readonly RadioPlaylistExportService $playlistExport,
        private readonly RadioStateService $state,
    ) {}

    public function playlist(Request $request): JsonResponse|\Illuminate\Http\Response
    {
        if ($request->query('format') === 'm3u') {
            return response($this->playlistExport->m3u(), 200, [
                'Content-Type' => 'audio/x-mpegurl',
            ]);
        }

        return response()->json([
            'station' => $this->stationPayload(),
            'tracks' => $this->playlistExport->liquidsoapPlaylist(),
            'jingles' => $this->playlistExport->activeJingles(),
            'podcasts' => $this->publishedPodcasts(),
            'reload_requested_at' => $this->state->reloadRequestedAt(),
        ]);
    }

    public function playlistM3u(): \Illuminate\Http\Response
    {
        $liqContent = $this->playlistExport->liq();
        $liqPath = storage_path('app/radio-cache/playlist.liq');
        file_put_contents($liqPath, $liqContent, LOCK_EX);

        return response($this->playlistExport->m3u(), 200, [
            'Content-Type' => 'audio/x-mpegurl',
        ]);
    }

    public function nowPlaying(Request $request): JsonResponse
    {
        $data = $request->all();

        // If only sound_id is provided (Liquidsoap GET update), look up the sound
        if (! empty($data['sound_id']) && empty($data['title'])) {
            $sound = \App\Models\Sound::query()->find($data['sound_id']);
            if ($sound) {
                $data['title'] = $sound->title;
                $data['artist'] = $sound->user?->name ?? 'Arborisis';
                $data['slug'] = $sound->slug;
                $data['duration'] = $sound->duration;
            }
        }

        // If only podcast_id is provided, look up the podcast
        if (! empty($data['podcast_id']) && empty($data['title'])) {
            $podcast = \App\Models\RadioPodcast::query()->find($data['podcast_id']);
            if ($podcast) {
                $data['title'] = $podcast->title;
                $data['artist'] = 'Arborisis Radio';
                $data['duration'] = (int) ($podcast->actual_duration_seconds ?? 180);
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'sound_id' => ['nullable', 'integer', 'exists:sounds,id'],
            'podcast_id' => ['nullable', 'integer', 'exists:radio_podcasts,id'],
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'cover' => ['nullable', 'string', 'max:2048'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'started_at' => ['nullable', 'date'],
            'slug' => ['nullable', 'string', 'max:255'],
            'kind' => ['nullable', 'string', 'in:sound,jingle,dj,silence,podcast'],
            'show_type' => ['nullable', 'string', 'in:podcast,flash,emission'],
            'next_up' => ['nullable', 'array'],
            'next_up.sound_id' => ['nullable', 'integer'],
            'next_up.title' => ['nullable', 'string', 'max:255'],
            'next_up.artist' => ['nullable', 'string', 'max:255'],
            'listeners' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        return response()->json([
            'now_playing' => $this->state->update($validator->validated()),
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'station' => $this->stationPayload(),
            'status' => $this->state->status(),
            'now_playing' => $this->state->current(),
            'history' => $this->state->history(),
        ]);
    }

    public function reload(): JsonResponse
    {
        $this->state->requestReload();

        return response()->json([
            'ok' => true,
            'reload_requested_at' => $this->state->reloadRequestedAt(),
        ]);
    }

    private function stationPayload(): array
    {
        $settings = RadioSetting::query()->first();

        return [
            'name' => $settings?->station_name ?? 'Arborisis Radio',
            'tagline' => $settings?->tagline,
            'engine' => $settings?->engine ?? config('radio.engine', 'icecast'),
            'public_stream_url' => $settings?->public_stream_url ?: config('radio.public_stream_url'),
            'icecast_base_url' => $settings?->icecast_base_url ?: config('radio.icecast_base_url'),
            'icecast_mount' => $settings?->icecast_mount ?: config('radio.icecast_mount'),
            'crossfade_seconds' => $settings?->crossfade_seconds ?? config('radio.crossfade_seconds', 4),
            'dj_enabled' => $settings?->dj_enabled ?? config('radio.dj.enabled', true),
            'dj_announcement_frequency' => $settings?->dj_announcement_frequency ?? config('radio.dj.announcement_frequency', 3),
            'discord_voice_channel_id' => $settings?->discord_voice_channel_id ?? config('radio.discord.voice_channel_id'),
            'discord_auto_join' => $settings?->discord_auto_join ?? config('radio.discord.auto_join', true),
            'podcast_enabled' => $settings?->podcast_enabled ?? config('radio.podcast.enabled', false),
            'podcast_interval_tracks' => $settings?->podcast_interval_tracks ?? config('radio.podcast.interval_tracks', 15),
        ];
    }

    private function publishedPodcasts(): array
    {
        return \App\Models\RadioPodcast::query()
            ->readyForAir()
            ->latest('published_at')
            ->limit(10)
            ->get()
            ->map(fn (\App\Models\RadioPodcast $podcast): array => [
                'id' => $podcast->id,
                'title' => $podcast->title,
                'description' => $podcast->description,
                'duration' => $podcast->actual_duration_seconds,
                'published_at' => $podcast->published_at?->toIso8601String(),
            ])
            ->all();
    }
}
