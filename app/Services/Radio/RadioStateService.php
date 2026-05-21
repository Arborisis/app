<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\Sound;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RadioStateService
{
    public const CACHE_KEY_NOW_PLAYING = 'radio:engine:now-playing';
    public const CACHE_KEY_HISTORY = 'radio:engine:history';
    public const CACHE_KEY_STATUS = 'radio:engine:status';
    public const CACHE_KEY_RELOAD_REQUESTED_AT = 'radio:engine:reload-requested-at';

    public function current(?RadioStreamService $fallback = null): ?array
    {
        $state = Cache::get(self::CACHE_KEY_NOW_PLAYING);

        if (is_array($state)) {
            return $state;
        }

        return $fallback?->getCurrentMetadata();
    }

    public function update(array $payload): array
    {
        $state = $this->normalizeState($payload);

        Cache::put(self::CACHE_KEY_NOW_PLAYING, $state, now()->addMinutes(30));
        Cache::put(self::CACHE_KEY_STATUS, [
            'online' => true,
            'last_seen_at' => now()->toIso8601String(),
            'source' => 'liquidsoap',
        ], now()->addMinutes(5));

        $this->pushHistory($state);

        return $state;
    }

    public function history(int $limit = 10): array
    {
        return array_slice(Cache::get(self::CACHE_KEY_HISTORY, []), 0, $limit);
    }

    public function status(): array
    {
        $status = Cache::get(self::CACHE_KEY_STATUS, [
            'online' => false,
            'last_seen_at' => null,
            'source' => config('radio.engine', 'icecast'),
        ]);

        $status['reload_requested_at'] = Cache::get(self::CACHE_KEY_RELOAD_REQUESTED_AT);

        return $status;
    }

    public function requestReload(): void
    {
        Cache::put(self::CACHE_KEY_RELOAD_REQUESTED_AT, now()->toIso8601String(), now()->addMinutes(10));
    }

    public function reloadRequestedAt(): ?string
    {
        return Cache::get(self::CACHE_KEY_RELOAD_REQUESTED_AT);
    }

    private function normalizeState(array $payload): array
    {
        $sound = isset($payload['sound_id'])
            ? Sound::with('user')->find($payload['sound_id'])
            : null;

        $podcast = isset($payload['podcast_id'])
            ? \App\Models\RadioPodcast::find($payload['podcast_id'])
            : null;

        $kind = $payload['kind'] ?? 'sound';

        $title = match ($kind) {
            'podcast' => $payload['title'] ?? $podcast?->title ?? 'Arborisis Podcast',
            default => $payload['title'] ?? $sound?->title ?? 'Arborisis Radio',
        };

        $artist = match ($kind) {
            'podcast' => $payload['artist'] ?? 'Arborisis Radio',
            default => $payload['artist'] ?? $sound?->user?->name ?? 'Arborisis',
        };

        return [
            'title' => $title,
            'artist' => $artist,
            'cover' => $payload['cover'] ?? $sound?->cover_url,
            'duration' => $payload['duration'] ?? $sound?->duration ?? $podcast?->actual_duration_seconds,
            'started_at' => isset($payload['started_at'])
                ? Carbon::parse($payload['started_at'])->toIso8601String()
                : now()->toIso8601String(),
            'sound_id' => $payload['sound_id'] ?? $sound?->id,
            'podcast_id' => $payload['podcast_id'] ?? $podcast?->id,
            'slug' => $payload['slug'] ?? $sound?->slug,
            'kind' => $kind,
            'show_type' => $payload['show_type'] ?? $podcast?->show_type?->value,
            'next_up' => $payload['next_up'] ?? null,
            'listeners' => $payload['listeners'] ?? null,
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function pushHistory(array $state): void
    {
        if (($state['kind'] ?? 'sound') !== 'sound' || empty($state['sound_id'])) {
            return;
        }

        $history = Cache::get(self::CACHE_KEY_HISTORY, []);
        $currentId = (int) $state['sound_id'];

        if (($history[0]['sound_id'] ?? null) === $currentId) {
            return;
        }

        array_unshift($history, [
            'sound_id' => $currentId,
            'title' => $state['title'],
            'artist' => $state['artist'],
            'slug' => $state['slug'],
            'cover' => $state['cover'],
            'duration' => $state['duration'],
            'played_at' => $state['started_at'],
        ]);

        Cache::put(self::CACHE_KEY_HISTORY, array_slice($history, 0, 30), now()->addDay());
    }
}
