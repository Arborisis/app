<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioJinglePlacement;
use App\Enums\RadioListenerSessionStatus;
use App\Models\RadioJingle;
use App\Models\RadioListenerSession;
use App\Models\RadioPodcast;
use App\Models\RadioSetting;
use App\Models\RadioSchedule;
use App\Models\Sound;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RadioStreamService
{
    private const CACHE_KEY_NOW_PLAYING = 'radio:now-playing';
    private const CACHE_KEY_HISTORY = 'radio:history';
    private const CACHE_KEY_LISTENERS = 'radio:listeners';
    private const CACHE_KEY_LISTENER_SESSIONS = 'radio:listener-sessions';
    private const CACHE_KEY_LISTENER_GENERATION = 'radio:listener-generation';
    private const CACHE_KEY_EPOCH = 'radio:epoch';
    private const CACHE_KEY_PLAYLIST = 'radio:playlist';

    private int $icyMetaint;
    private int $chunkSize;
    private int $historyLimit;
    private bool $shuffle;
    private bool $enabled;
    private int $tempUrlTtlMinutes;
    private bool $loop;
    private int $gapMs;
    private int $listenerTtlSeconds;
    private ?int $maxListenersDisplay;
    private ?string $activeListenerId = null;
    private ?int $activeListenerGeneration = null;

    public function __construct()
    {
        $settings = Schema::hasTable('radio_settings') ? RadioSetting::query()->first() : null;

        $this->icyMetaint = $settings?->icy_metaint ?? config('radio.icy_metaint', 8192);
        $this->chunkSize = config('radio.chunk_size', 8192);
        $this->historyLimit = $settings?->history_limit ?? config('radio.history_limit', 20);
        $this->shuffle = $settings?->shuffle_enabled ?? config('radio.playlist_shuffle', true);
        $this->enabled = $settings?->is_enabled ?? true;
        $this->tempUrlTtlMinutes = config('radio.temp_url_ttl_minutes', 60);
        $this->loop = $settings?->loop_enabled ?? config('radio.loop', true);
        $this->gapMs = $settings?->gap_ms ?? config('radio.gap_ms', 500);
        $this->listenerTtlSeconds = $settings?->listener_ttl_seconds ?? config('radio.listener_ttl_seconds', 120);
        $this->maxListenersDisplay = $settings?->max_listeners_display;
    }

    public function getPlaylist(): Collection
    {
        if (! $this->enabled) {
            return new Collection();
        }

        $scheduledPlaylist = $this->getScheduledPlaylist();

        if ($scheduledPlaylist->isNotEmpty()) {
            return $scheduledPlaylist;
        }

        return Sound::public()
            ->with(['user', 'soundFile'])
            ->whereHas('soundFile', function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('radio_path')
                        ->orWhere('mime_type', 'audio/mpeg');
                });
            })
            ->get();
    }

    private function getScheduledPlaylist(): Collection
    {
        if (! Schema::hasTable('radio_schedules') || ! Schema::hasTable('radio_schedule_sound')) {
            return new Collection();
        }

        $schedule = RadioSchedule::query()
            ->with(['sounds.user', 'sounds.soundFile'])
            ->where('is_active', true)
            ->whereHas('sounds')
            ->orderByDesc('priority')
            ->orderBy('starts_at')
            ->get()
            ->first(fn (RadioSchedule $schedule): bool => $schedule->isCurrentlyActive());

        if (! $schedule) {
            return new Collection();
        }

        return $schedule->sounds
            ->filter(fn (Sound $sound): bool => $sound->isPublic())
            ->values();
    }

    public function getPlaylistMimeType(): string
    {
        $playlist = $this->getDeterministicPlaylist();

        if ($playlist->isEmpty()) {
            return 'audio/mpeg';
        }

        $hasRadio = $playlist->contains(fn (Sound $s) => $s->soundFile?->radio_path !== null);

        if ($hasRadio) {
            return 'audio/mpeg';
        }

        $mimes = $playlist->pluck('soundFile.mime_type')->filter()->unique()->values();

        if ($mimes->count() === 1) {
            return $mimes->first();
        }

        return 'audio/mpeg';
    }

    public function supportsIcyMetadata(): bool
    {
        $mime = $this->getPlaylistMimeType();

        return str_starts_with($mime, 'audio/mpeg') || str_starts_with($mime, 'audio/mp3');
    }

    /**
     * Retourne les sons effectivement jouables (MP3 ou convertis en MP3).
     * Seuls les IDs sont mis en cache pour éviter la sérialisation d'objets Eloquent.
     */
    public function getPlayablePlaylist(): Collection
    {
        $ids = Cache::remember(self::CACHE_KEY_PLAYLIST, 60, function () {
            $sounds = $this->getPlaylist();

            if ($sounds->isEmpty()) {
                $fallbackIds = config('radio.fallback_sounds', []);
                if (! empty($fallbackIds)) {
                    $sounds = Sound::with(['user', 'soundFile'])
                        ->whereIn('id', $fallbackIds)
                        ->whereHas('soundFile')
                        ->get();
                }
            }

            return $sounds->filter(fn (Sound $sound) => $this->resolvePlayableSource($sound) !== null)
                ->pluck('id')
                ->all();
        });

        if (empty($ids)) {
            return new Collection();
        }

        return Sound::with(['user', 'soundFile'])
            ->whereIn('id', $ids)
            ->get();
    }

    /**
     * Playlist ordonnée de manière déterministe.
     * Si shuffle est activé, l'ordre change chaque jour mais est identique
     * pour tous les auditeurs (seed basé sur la date).
     */
    public function getDeterministicPlaylist(): Collection
    {
        $sounds = $this->getPlayablePlaylist();

        if ($sounds->isEmpty()) {
            return $sounds;
        }

        if ($this->shuffle) {
            $seed = now()->format('Y-m-d');

            return $sounds->sortBy(fn (Sound $s) => md5($seed.$s->id))->values();
        }

        return $sounds->sortBy('id')->values();
    }

    private function getEpoch(): int
    {
        return (int) Cache::get(self::CACHE_KEY_EPOCH, now()->startOfDay()->timestamp);
    }

    private function calculateCycleDuration(Collection $playlist): int
    {
        $total = 0;
        foreach ($playlist as $sound) {
            $total += (int) ($sound->duration ?? 0);
        }
        $total += $playlist->count() * (int) ceil($this->gapMs / 1000);

        return max(1, $total);
    }

    /**
     * Résout le son en cours à un timestamp Unix donné.
     * Retourne un tableau [sound, index, elapsed] ou null.
     */
    public function resolveScheduleAt(int $timestamp): ?array
    {
        $playlist = $this->getDeterministicPlaylist();

        if ($playlist->isEmpty()) {
            return null;
        }

        $epoch = $this->getEpoch();
        $elapsed = max(0, $timestamp - $epoch);
        $cycleDuration = $this->calculateCycleDuration($playlist);
        $position = $elapsed % $cycleDuration;
        $currentPos = 0;

        foreach ($playlist as $index => $sound) {
            $duration = (int) ($sound->duration ?? 0);
            $end = $currentPos + $duration;

            if ($position >= $currentPos && $position < $end) {
                return [
                    'sound' => $sound,
                    'index' => $index,
                    'elapsed' => $position - $currentPos,
                ];
            }

            $currentPos = $end + (int) ceil($this->gapMs / 1000);
        }

        // Fallback théorique (ne devrait pas arriver si cycleDuration > 0)
        return [
            'sound' => $playlist->first(),
            'index' => 0,
            'elapsed' => 0,
        ];
    }

    public function resolveCurrentSound(): ?Sound
    {
        $schedule = $this->resolveScheduleAt(time());

        return $schedule['sound'] ?? null;
    }

    public function resolveCurrentIndex(): int
    {
        $schedule = $this->resolveScheduleAt(time());

        return $schedule['index'] ?? 0;
    }

    public function resolveNextSound(): ?Sound
    {
        $playlist = $this->getDeterministicPlaylist();

        if ($playlist->isEmpty()) {
            return null;
        }

        $currentIndex = $this->resolveCurrentIndex();

        return $playlist[($currentIndex + 1) % $playlist->count()] ?? null;
    }

    public function resolveHistory(int $limit = 5): Collection
    {
        $playlist = $this->getDeterministicPlaylist();

        if ($playlist->isEmpty()) {
            return new Collection();
        }

        $currentIndex = $this->resolveCurrentIndex();
        $historyIds = [];
        $count = $playlist->count();

        for ($i = 1; $i <= $limit; $i++) {
            $idx = ($currentIndex - $i) % $count;
            if ($idx < 0) {
                $idx += $count;
            }
            $historyIds[] = $playlist[$idx]->id;
        }

        if (empty($historyIds)) {
            return new Collection();
        }

        return Sound::with('user')
            ->whereIn('id', $historyIds)
            ->get()
            ->sortBy(function ($sound) use ($historyIds) {
                return array_search($sound->id, $historyIds, true);
            })
            ->values();
    }

    public function getCurrentSound(): ?Sound
    {
        return $this->resolveCurrentSound();
    }

    public function getCurrentMetadata(): ?array
    {
        $sound = $this->resolveCurrentSound();

        if (! $sound) {
            return null;
        }

        $schedule = $this->resolveScheduleAt(time());
        $startedAt = now()->subSeconds($schedule['elapsed'] ?? 0);

        return [
            'title' => $sound->title,
            'artist' => $sound->user?->name ?? 'Arborisis',
            'cover' => $sound->cover_url,
            'duration' => $sound->duration,
            'started_at' => $startedAt->toIso8601String(),
            'sound_id' => $sound->id,
            'slug' => $sound->slug,
        ];
    }

    public function getHistory(): array
    {
        return $this->resolveHistory($this->historyLimit)->pluck('id')->all();
    }

    public function getHistorySounds(int $limit = 5): Collection
    {
        return $this->resolveHistory($limit);
    }

    public function incrementListeners(): void
    {
        $this->activeListenerId = (string) Str::uuid();
        $this->activeListenerGeneration = $this->getListenerGeneration();
        if (Schema::hasTable('radio_listener_sessions')) {
            app(ListenerSessionTracker::class)->start($this->activeListenerId);
        }
        $this->touchActiveListener();
    }

    public function decrementListeners(): void
    {
        if (! $this->activeListenerId) {
            $this->resetLegacyListenerCount();

            return;
        }

        $sessions = $this->getListenerSessions();
        unset($sessions[$this->activeListenerId]);
        Cache::put(self::CACHE_KEY_LISTENER_SESSIONS, $this->pruneListenerSessions($sessions), now()->addSeconds($this->listenerTtlSeconds * 2));
        Cache::forget($this->listenerSessionKey($this->activeListenerId));
        if (Schema::hasTable('radio_listener_sessions')) {
            app(ListenerSessionTracker::class)->close($this->activeListenerId);
        }
        $this->activeListenerId = null;
        $this->activeListenerGeneration = null;
        $this->resetLegacyListenerCount();
    }

    public function getListenerCount(): int
    {
        $sessions = $this->pruneListenerSessions($this->getListenerSessions());
        Cache::put(self::CACHE_KEY_LISTENER_SESSIONS, $sessions, now()->addSeconds($this->listenerTtlSeconds * 2));
        $this->resetLegacyListenerCount();

        $dbCount = Schema::hasTable('radio_listener_sessions')
            ? app(ListenerSessionTracker::class)->activeCount()
            : 0;
        $count = max(count($sessions), $dbCount);

        return $this->maxListenersDisplay === null ? $count : min($count, $this->maxListenersDisplay);
    }

    public function resetListenerCount(): void
    {
        foreach (array_keys($this->getListenerSessions()) as $listenerId) {
            Cache::forget($this->listenerSessionKey($listenerId));
        }

        Cache::forget(self::CACHE_KEY_LISTENER_SESSIONS);
        Cache::forever(self::CACHE_KEY_LISTENER_GENERATION, (int) (microtime(true) * 1000));
        if (Schema::hasTable('radio_listener_sessions')) {
            RadioListenerSession::query()
                ->where('status', RadioListenerSessionStatus::Active)
                ->update([
                    'ended_at' => now(),
                    'status' => RadioListenerSessionStatus::Closed,
                ]);
        }
        $this->resetLegacyListenerCount();
    }

    public function streamToOutput(callable $outputCallback, bool $injectIcy = false): void
    {
        $this->incrementListeners();

        try {
            $playlist = $this->getDeterministicPlaylist();

            if ($playlist->isEmpty()) {
                Log::warning('Radio stream: no playable sounds available');
                while (! connection_aborted()) {
                    $this->streamSilence($outputCallback);
                    sleep(5);
                }

                return;
            }

            $startIndex = $this->resolveCurrentIndex();
            $index = $startIndex;
            $consecutiveFailures = 0;
            $playlistCount = $playlist->count();
            $tracksStreamed = 0;
            $podcastInterval = $this->isPodcastEnabled() ? (int) config('radio.podcast.interval_tracks', 15) : 0;

            while (! connection_aborted()) {
                $sound = $playlist[$index] ?? null;

                if (! $sound) {
                    if (! $this->loop) {
                        Log::info('Radio stream: loop disabled, ending stream');
                        break;
                    }
                    $index = 0;
                    $sound = $playlist[0];
                }

                $this->streamDueJingle(RadioJinglePlacement::BeforeTrack, $tracksStreamed, $outputCallback, $injectIcy);
                $bytesStreamed = $this->streamSound($sound, $outputCallback, $injectIcy);

                if ($bytesStreamed === 0) {
                    $consecutiveFailures++;
                    Log::warning('Radio stream: sound streamed 0 bytes, throttling', [
                        'sound_id' => $sound->id,
                        'consecutive_failures' => $consecutiveFailures,
                        'playlist_count' => $playlistCount,
                    ]);

                    if ($consecutiveFailures >= $playlistCount) {
                        Log::error('Radio stream: all sounds failed, stopping stream to prevent worker saturation');
                        break;
                    }

                    sleep(1);
                } else {
                    $consecutiveFailures = 0;
                    $tracksStreamed++;
                }

                $this->streamDueJingle(RadioJinglePlacement::AfterTrack, $tracksStreamed, $outputCallback, $injectIcy);
                $this->streamDueJingle(RadioJinglePlacement::BetweenBlocks, $tracksStreamed, $outputCallback, $injectIcy);
                $this->streamHourlyJingles($outputCallback, $injectIcy);

                if ($podcastInterval > 0 && $tracksStreamed > 0 && $tracksStreamed % $podcastInterval === 0) {
                    $this->streamLatestPodcast($outputCallback, $injectIcy);
                }

                if ($this->gapMs > 0) {
                    $this->streamGap($outputCallback);
                }

                $index++;

                if ($index >= $playlistCount) {
                    if (! $this->loop) {
                        Log::info('Radio stream: reached end of playlist, loop disabled');
                        break;
                    }
                    $index = 0;
                }
            }
        } finally {
            $this->decrementListeners();
        }
    }

    private function streamSound(Sound $sound, callable $outputCallback, bool $icyEnabled = true): int
    {
        if (! $sound->soundFile) {
            Log::warning('Radio stream: sound has no file', ['sound_id' => $sound->id]);

            return 0;
        }

        $source = $this->resolvePlayableSource($sound);

        if (! $source) {
            Log::warning('Radio stream: no readable MPEG source for sound', [
                'sound_id' => $sound->id,
                'disk' => $sound->soundFile->disk,
                'path' => $sound->soundFile->path,
                'radio_path' => $sound->soundFile->radio_path,
                'mime_type' => $sound->soundFile->mime_type,
                'radio_mime_type' => $sound->soundFile->radio_mime_type,
            ]);

            return 0;
        }

        ['disk' => $disk, 'path' => $path] = $source;
        $handle = Storage::disk($disk)->readStream($path);

        if (! $handle) {
            Log::warning('Radio stream: failed to open audio stream', [
                'sound_id' => $sound->id,
                'disk' => $disk,
                'path' => $path,
            ]);

            return 0;
        }

        $totalBytes = 0;

        try {
            $bytesSinceMeta = 0;
            $title = $sound->title;
            $artist = $sound->user?->name ?? 'Arborisis';

            while (! feof($handle) && ! connection_aborted()) {
                $chunk = fread($handle, $this->chunkSize);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $this->touchActiveListener();
                $outputCallback($chunk);
                $chunkLen = strlen($chunk);
                $totalBytes += $chunkLen;

                if ($icyEnabled) {
                    $bytesSinceMeta += $chunkLen;

                    if ($bytesSinceMeta >= $this->icyMetaint) {
                        $meta = $this->generateIcyMetadata($title, $artist);
                        $outputCallback($meta);
                        $bytesSinceMeta = 0;
                    }
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        } finally {
            fclose($handle);
        }

        return $totalBytes;
    }

    private function streamDueJingle(
        RadioJinglePlacement $placement,
        int $tracksStreamed,
        callable $outputCallback,
        bool $icyEnabled
    ): void {
        $sequence = $placement === RadioJinglePlacement::BeforeTrack ? $tracksStreamed + 1 : $tracksStreamed;

        if ($sequence < 1) {
            return;
        }

        $jingles = $this->getActiveJingles($placement)
            ->filter(fn (RadioJingle $jingle): bool => $sequence % max(1, $jingle->frequency) === 0)
            ->values();

        if ($jingles->isEmpty()) {
            return;
        }

        $jingle = $jingles[$sequence % $jingles->count()];
        $this->streamJingle($jingle, $outputCallback, $icyEnabled);
    }

    private function getActiveJingles(RadioJinglePlacement $placement): Collection
    {
        if (! Schema::hasTable('radio_jingles')) {
            return new Collection();
        }

        return RadioJingle::query()
            ->where('is_active', true)
            ->where('placement', $placement)
            ->orderBy('id')
            ->get()
            ->filter(fn (RadioJingle $jingle): bool => $jingle->isCurrentlyActive())
            ->filter(fn (RadioJingle $jingle): bool => Storage::disk($jingle->disk)->exists($jingle->path))
            ->values();
    }

    private function streamHourlyJingles(callable $outputCallback, bool $icyEnabled): void
    {
        foreach ($this->getActiveJingles(RadioJinglePlacement::Hourly) as $jingle) {
            $cacheKey = 'radio:jingle-hour:'.$jingle->id.':'.now()->format('YmdH');

            if (! Cache::add($cacheKey, true, now()->addMinutes(70))) {
                continue;
            }

            $this->streamJingle($jingle, $outputCallback, $icyEnabled);
        }
    }

    private function isPodcastEnabled(): bool
    {
        return config('radio.podcast.enabled', false)
            || config('radio.host.flash_enabled', false)
            || config('radio.host.emission_enabled', false);
    }

    private function streamLatestPodcast(callable $outputCallback, bool $icyEnabled): void
    {
        $podcast = RadioPodcast::query()
            ->readyForAir()
            ->latest('published_at')
            ->first();

        if (! $podcast || ! $podcast->disk || ! $podcast->path) {
            return;
        }

        if (! Storage::disk($podcast->disk)->exists($podcast->path)) {
            Log::warning('Radio stream: podcast file not found', [
                'podcast_id' => $podcast->id,
                'disk' => $podcast->disk,
                'path' => $podcast->path,
            ]);

            return;
        }

        $handle = Storage::disk($podcast->disk)->readStream($podcast->path);

        if (! $handle) {
            Log::warning('Radio stream: failed to open podcast stream', [
                'podcast_id' => $podcast->id,
            ]);

            return;
        }

        $title = $podcast->title;
        $bytesSinceMeta = 0;

        try {
            while (! feof($handle) && ! connection_aborted()) {
                $chunk = fread($handle, $this->chunkSize);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $this->touchActiveListener();
                $outputCallback($chunk);
                $chunkLen = strlen($chunk);

                if ($icyEnabled) {
                    $bytesSinceMeta += $chunkLen;

                    if ($bytesSinceMeta >= $this->icyMetaint) {
                        $outputCallback($this->generateIcyMetadata($title, 'Arborisis Radio'));
                        $bytesSinceMeta = 0;
                    }
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        } finally {
            fclose($handle);
        }

        Log::info('Radio stream: podcast streamed', ['podcast_id' => $podcast->id, 'title' => $title]);
    }

    private function streamJingle(RadioJingle $jingle, callable $outputCallback, bool $icyEnabled): int
    {
        $handle = Storage::disk($jingle->disk)->readStream($jingle->path);

        if (! $handle) {
            Log::warning('Radio stream: failed to open jingle stream', [
                'jingle_id' => $jingle->id,
                'disk' => $jingle->disk,
                'path' => $jingle->path,
            ]);

            return 0;
        }

        $totalBytes = 0;
        $bytesSinceMeta = 0;

        try {
            while (! feof($handle) && ! connection_aborted()) {
                $chunk = fread($handle, $this->chunkSize);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                $this->touchActiveListener();
                $outputCallback($chunk);
                $chunkLen = strlen($chunk);
                $totalBytes += $chunkLen;

                if ($icyEnabled) {
                    $bytesSinceMeta += $chunkLen;

                    if ($bytesSinceMeta >= $this->icyMetaint) {
                        $outputCallback($this->generateIcyMetadata($jingle->name, 'Arborisis Radio'));
                        $bytesSinceMeta = 0;
                    }
                }

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        } finally {
            fclose($handle);
        }

        return $totalBytes;
    }

    private function touchActiveListener(): void
    {
        if (! $this->activeListenerId) {
            return;
        }

        if ($this->activeListenerGeneration !== $this->getListenerGeneration()) {
            return;
        }

        $expiresAt = now()->addSeconds($this->listenerTtlSeconds)->timestamp;
        $sessions = $this->pruneListenerSessions($this->getListenerSessions());
        $sessions[$this->activeListenerId] = $expiresAt;

        Cache::put($this->listenerSessionKey($this->activeListenerId), true, now()->addSeconds($this->listenerTtlSeconds));
        Cache::put(self::CACHE_KEY_LISTENER_SESSIONS, $sessions, now()->addSeconds($this->listenerTtlSeconds * 2));
        if (Schema::hasTable('radio_listener_sessions')) {
            app(ListenerSessionTracker::class)->heartbeat($this->activeListenerId);
        }
    }

    private function getListenerSessions(): array
    {
        $sessions = Cache::get(self::CACHE_KEY_LISTENER_SESSIONS, []);

        return is_array($sessions) ? $sessions : [];
    }

    private function pruneListenerSessions(array $sessions): array
    {
        $now = now()->timestamp;

        return array_filter(
            $sessions,
            fn (mixed $expiresAt, string $listenerId): bool => (int) $expiresAt > $now
                && Cache::has($this->listenerSessionKey($listenerId)),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function listenerSessionKey(string $listenerId): string
    {
        return 'radio:listener:'.$listenerId;
    }

    private function getListenerGeneration(): int
    {
        return (int) Cache::get(self::CACHE_KEY_LISTENER_GENERATION, 1);
    }

    private function resetLegacyListenerCount(): void
    {
        Cache::put(self::CACHE_KEY_LISTENERS, 0);
    }

    private function resolvePlayableSource(Sound $sound): ?array
    {
        $soundFile = $sound->soundFile;

        if (! $soundFile) {
            return null;
        }

        $disk = $soundFile->disk;

        if (
            $soundFile->radio_path
            && $soundFile->radio_mime_type === 'audio/mpeg'
            && Storage::disk($disk)->exists($soundFile->radio_path)
        ) {
            return [
                'disk' => $disk,
                'path' => $soundFile->radio_path,
            ];
        }

        if (
            $soundFile->mime_type === 'audio/mpeg'
            && Storage::disk($disk)->exists($soundFile->path)
        ) {
            return [
                'disk' => $disk,
                'path' => $soundFile->path,
            ];
        }

        return null;
    }

    private function streamGap(callable $outputCallback): void
    {
        if ($this->gapMs <= 0) {
            return;
        }

        usleep($this->gapMs * 1000);
    }

    private function streamSilence(callable $outputCallback): void
    {
        $silenceFile = config('radio.silence_file') ?: storage_path('app/radio_silence.mp3');

        if ($silenceFile && file_exists($silenceFile)) {
            $handle = fopen($silenceFile, 'r');
            if ($handle) {
                while (! feof($handle) && ! connection_aborted()) {
                    $chunk = fread($handle, $this->chunkSize);
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    $outputCallback($chunk);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                fclose($handle);

                return;
            }
        }

        $silence = str_repeat("\x00", $this->chunkSize);
        for ($i = 0; $i < 10 && ! connection_aborted(); $i++) {
            $outputCallback($silence);
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
            usleep(100000);
        }
    }

    public function generateIcyMetadata(string $title, string $artist): string
    {
        $meta = "StreamTitle='{$title} - {$artist}';";
        $length = (int) ceil(strlen($meta) / 16);
        $padded = str_pad($meta, $length * 16, "\x00");

        return chr($length).$padded;
    }

    public function getAudioUrl(Sound $sound): ?string
    {
        if (! $sound->soundFile) {
            return null;
        }

        $disk = $sound->soundFile->disk;
        $path = $sound->soundFile->path;

        if ($disk === 'r2') {
            return app(\App\Services\Storage\SignedUrlService::class)->url($disk, $path, $this->tempUrlTtlMinutes);
        }

        if ($disk === 'audio' || $disk === 's3') {
            return Storage::disk($disk)->temporaryUrl(
                $path,
                now()->addMinutes($this->tempUrlTtlMinutes)
            );
        }

        return Storage::disk($disk)->url($path);
    }
}
