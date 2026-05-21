<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\RadioDjAnnouncement;
use App\Models\RadioJingle;
use App\Models\RadioPodcast;
use App\Models\Sound;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RadioAudioCacheService
{
    private const ALLOWED_TYPES = ['sounds', 'jingles', 'podcasts', 'dj'];

    public function __construct(
        private readonly RadioAudioNormalizationService $normalization,
    ) {}

    public function warmSound(Sound $sound): string
    {
        return $this->warmModel('sounds', $sound->id, function () use ($sound): ?string {
            $soundFile = $sound->soundFile;

            if (! $soundFile) {
                return null;
            }

            $disk = $soundFile->disk;
            $sourcePath = null;

            if (
                $soundFile->radio_path
                && $soundFile->radio_mime_type === 'audio/mpeg'
                && Storage::disk($disk)->exists($soundFile->radio_path)
            ) {
                $sourcePath = $soundFile->radio_path;
            } elseif (
                $soundFile->mime_type === 'audio/mpeg'
                && Storage::disk($disk)->exists($soundFile->path)
            ) {
                $sourcePath = $soundFile->path;
            }

            if (! $sourcePath) {
                return null;
            }

            return $this->downloadAndNormalize($disk, $sourcePath);
        });
    }

    public function warmJingle(RadioJingle $jingle): string
    {
        return $this->warmModel('jingles', $jingle->id, function () use ($jingle): ?string {
            if (! Storage::disk($jingle->disk)->exists($jingle->path)) {
                return null;
            }

            return $this->downloadAndNormalize($jingle->disk, $jingle->path);
        });
    }

    public function warmPodcast(RadioPodcast $podcast): string
    {
        return $this->warmModel('podcasts', $podcast->id, function () use ($podcast): ?string {
            if (! $podcast->path || ! Storage::disk($podcast->disk)->exists($podcast->path)) {
                return null;
            }

            return $this->downloadAndNormalize($podcast->disk, $podcast->path);
        });
    }

    public function warmDjAnnouncement(RadioDjAnnouncement $announcement): string
    {
        return $this->warmModel('dj', $announcement->id, function () use ($announcement): ?string {
            if (! $announcement->path || ! Storage::disk($announcement->disk)->exists($announcement->path)) {
                return null;
            }

            return $this->downloadAndNormalize($announcement->disk, $announcement->path);
        });
    }

    public function exists(string $type, int $id): bool
    {
        $this->ensureAllowedType($type);

        return Storage::disk($this->cacheDisk())->exists("{$type}/{$id}.mp3");
    }

    public function pathFor(string $type, int $id): string
    {
        $this->ensureAllowedType($type);

        return Storage::disk($this->cacheDisk())->path("{$type}/{$id}.mp3");
    }

    public function urlFor(string $type, int $id): string
    {
        $this->ensureAllowedType($type);
        $base = rtrim(config('radio.audio_cache.url_base', config('app.url')), '/');

        return "{$base}/radio/cache/{$type}/{$id}";
    }

    public function localPathFor(string $type, int $id): string
    {
        $this->ensureAllowedType($type);

        return "/radio-cache/{$type}/{$id}.mp3";
    }

    public function rebuild(): void
    {
        $this->cleanup();

        foreach (Sound::public()->with('soundFile')->cursor() as $sound) {
            try {
                $this->warmSound($sound);
            } catch (\Throwable $e) {
                Log::warning('Radio cache rebuild: failed to warm sound', [
                    'sound_id' => $sound->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach (RadioJingle::query()->where('is_active', true)->cursor() as $jingle) {
            try {
                $this->warmJingle($jingle);
            } catch (\Throwable $e) {
                Log::warning('Radio cache rebuild: failed to warm jingle', [
                    'jingle_id' => $jingle->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach (RadioDjAnnouncement::query()->cursor() as $ann) {
            try {
                $this->warmDjAnnouncement($ann);
            } catch (\Throwable $e) {
                Log::warning('Radio cache rebuild: failed to warm DJ announcement', [
                    'announcement_id' => $ann->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        foreach (RadioPodcast::query()->published()->cursor() as $podcast) {
            try {
                $this->warmPodcast($podcast);
            } catch (\Throwable $e) {
                Log::warning('Radio cache rebuild: failed to warm podcast', [
                    'podcast_id' => $podcast->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    public function cleanup(): void
    {
        $disk = Storage::disk($this->cacheDisk());
        $root = $disk->path('');

        foreach (self::ALLOWED_TYPES as $type) {
            $typePath = $root . $type;
            if (! is_dir($typePath)) {
                continue;
            }

            $files = glob($typePath . '/*.mp3');
            if ($files === false) {
                continue;
            }

            $activeIds = $this->activeIdsForType($type);

            foreach ($files as $file) {
                $basename = basename($file, '.mp3');
                if (! is_numeric($basename)) {
                    continue;
                }

                $id = (int) $basename;
                if (! in_array($id, $activeIds, true)) {
                    @unlink($file);
                    Log::info('Radio cache cleanup: removed orphan', [
                        'type' => $type,
                        'id' => $id,
                    ]);
                }
            }
        }
    }

    private function warmModel(string $type, int $id, callable $fetcher): string
    {
        $this->ensureAllowedType($type);

        if ($this->exists($type, $id)) {
            return $this->pathFor($type, $id);
        }

        $tempPath = $fetcher();

        if (! $tempPath || ! file_exists($tempPath)) {
            throw new \RuntimeException("Failed to fetch source for {$type} #{$id}");
        }

        $destination = $this->pathFor($type, $id);
        $dir = dirname($destination);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
            @chmod($dir, 0755);
        }

        if ($this->normalization->isAlreadyNormalized($tempPath)) {
            rename($tempPath, $destination);
        } else {
            $success = $this->normalization->normalize($tempPath, $destination);
            @unlink($tempPath);

            if (! $success) {
                throw new \RuntimeException("Normalization failed for {$type} #{$id}");
            }
        }

        return $destination;
    }

    private function downloadAndNormalize(string $disk, string $sourcePath): string
    {
        $stream = Storage::disk($disk)->readStream($sourcePath);

        if (! $stream) {
            throw new \RuntimeException('Cannot open source stream');
        }

        $tempOriginal = sys_get_temp_dir() . '/' . Str::uuid() . '_' . basename($sourcePath);
        $written = file_put_contents($tempOriginal, $stream);
        fclose($stream);

        if ($written === false || $written === 0) {
            @unlink($tempOriginal);
            throw new \RuntimeException('Failed to write temp file');
        }

        return $tempOriginal;
    }

    private function ensureAllowedType(string $type): void
    {
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid cache type: {$type}");
        }
    }

    private function cacheDisk(): string
    {
        return config('radio.audio_cache.disk', 'radio_cache');
    }

    /**
     * @return list<int>
     */
    private function activeIdsForType(string $type): array
    {
        return match ($type) {
            'sounds' => Sound::public()
                ->whereHas('soundFile', fn ($q) => $q->whereNotNull('path'))
                ->pluck('id')
                ->all(),
            'jingles' => RadioJingle::query()->where('is_active', true)->pluck('id')->all(),
            'podcasts' => RadioPodcast::query()->published()->pluck('id')->all(),
            'dj' => RadioDjAnnouncement::query()->pluck('id')->all(),
            default => [],
        };
    }
}
