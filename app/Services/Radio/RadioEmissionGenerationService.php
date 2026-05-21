<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use App\Models\RadioPodcast;
use App\Models\Sound;
use App\Services\AI\ElevenLabsService;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RadioEmissionGenerationService
{
    public function __construct(
        private readonly OpenRouterService $openRouter,
        private readonly ElevenLabsService $elevenLabs,
        private readonly RadioHostContextService $contextService,
        private readonly RadioAudioNormalizationService $normalization,
        private readonly RadioAudioProductionService $production,
        private readonly RadioAudioCacheService $cache,
    ) {}

    public function canGenerate(): bool
    {
        if (empty(config('services.openrouter.api_key'))) {
            return false;
        }

        if (empty(config('services.elevenlabs.api_key'))) {
            return false;
        }

        $pendingCount = RadioPodcast::query()
            ->whereIn('status', [RadioPodcastStatus::Pending, RadioPodcastStatus::Generating])
            ->where('show_type', RadioShowType::Emission->value)
            ->count();

        return $pendingCount < 2;
    }

    public function generate(): ?RadioPodcast
    {
        if (! $this->canGenerate()) {
            Log::info('Emission generation skipped: pipeline busy or missing keys');

            return null;
        }

        $minSounds = (int) config('radio.host.emission_min_sounds', 3);

        $podcast = RadioPodcast::query()->create([
            'show_type' => RadioShowType::Emission->value,
            'status' => RadioPodcastStatus::Pending,
            'title' => 'Generating emission...',
            'target_duration_seconds' => $minSounds * 120 + 180,
        ]);

        $tempFiles = [];

        try {
            $context = $this->contextService->gather();

            $podcast->update([
                'context_json' => $context,
            ]);

            $sounds = $this->selectSoundsForEmission();

            if ($sounds->isEmpty()) {
                throw new \RuntimeException('No suitable sounds found for emission');
            }

            $scriptResult = $this->openRouter->generateEmissionScript($sounds->all(), $context);

            if (! $scriptResult) {
                throw new \RuntimeException('OpenRouter emission script generation failed');
            }

            $script = $scriptResult['script'];
            $estimatedDuration = (int) $script['estimated_duration_seconds'];

            if ($estimatedDuration < 480 || $estimatedDuration > 900) {
                throw new \RuntimeException("Estimated emission duration {$estimatedDuration}s out of bounds [480, 900]");
            }

            $podcast->update([
                'status' => RadioPodcastStatus::Generating,
                'script_json' => $script,
                'sound_ids' => $sounds->pluck('id')->all(),
            ]);

            $voiceId = config('radio.host.host_voice_id')
                ?? config('radio.podcast.voice_id')
                ?? config('services.elevenlabs.voice_id');

            $segmentFiles = [];

            foreach ($script['segments'] as $segment) {
                $type = $segment['type'];

                if (in_array($type, ['intro', 'sound_intro', 'sound_outro', 'outro'], true)) {
                    $ttsFile = $this->synthesizeSegment($segment['text'], $voiceId);
                    if (! $ttsFile) {
                        throw new \RuntimeException("TTS synthesis failed for segment type={$type}");
                    }
                    $tempFiles[] = $ttsFile;
                    $segmentFiles[] = $ttsFile;
                } elseif ($type === 'sound') {
                    $soundId = (int) $segment['sound_id'];
                    $sound = $sounds->first(fn (Sound $s) => $s->id === $soundId);
                    if (! $sound) {
                        throw new \RuntimeException("Sound {$soundId} not in selection");
                    }
                    $audioPath = $this->resolveSoundAudioPath($sound);
                    if (! $audioPath) {
                        throw new \RuntimeException("Cannot resolve audio for sound {$soundId}");
                    }
                    $tempFiles[] = $audioPath;
                    $segmentFiles[] = $audioPath;
                }
            }

            $assembled = $this->production->produceSegmentedShow($segmentFiles, [
                'show_type' => RadioShowType::Emission->value,
                'production_preset' => RadioProductionPreset::EmissionCinematic->value,
                'title' => $script['title'] ?? null,
                'theme' => $script['description'] ?? null,
                'description' => $script['description'] ?? null,
                'script' => $script,
                'context' => $context,
                'selected_sounds' => $sounds->map(fn (Sound $sound) => [
                    'id' => $sound->id,
                    'title' => $sound->title,
                    'category' => $sound->category?->name,
                    'environment' => $sound->environment?->name,
                    'duration_seconds' => $sound->duration,
                ])->values()->all(),
            ]);

            foreach ($tempFiles as $f) {
                @unlink($f);
            }
            $tempFiles = [];

            if (! $assembled || ! file_exists($assembled)) {
                throw new \RuntimeException('Emission FFmpeg concat failed');
            }

            $actualDuration = $this->normalization->probeDuration($assembled);

            $minDurationFloor = $minSounds * 90;
            if ($actualDuration === null || $actualDuration < $minDurationFloor || $actualDuration > 1200) {
                @unlink($assembled);
                throw new \RuntimeException("Actual emission duration {$actualDuration}s out of bounds [{$minDurationFloor}, 1200]");
            }

            $normalizedFile = $assembled . '_norm.mp3';
            $normSuccess = $this->normalization->normalize($assembled, $normalizedFile);
            @unlink($assembled);

            if (! $normSuccess || ! file_exists($normalizedFile)) {
                throw new \RuntimeException('Emission normalization failed');
            }

            $disk = config('radio.host.storage_disk', 'r2');
            $path = 'radio/podcasts/' . $podcast->id . '.mp3';
            $stream = fopen($normalizedFile, 'r');
            Storage::disk($disk)->put($path, $stream);
            fclose($stream);
            @unlink($normalizedFile);

            $ttsText = implode("\n\n", array_filter(
                array_map(fn ($s) => $s['text'] ?? null, array_filter($script['segments'], fn ($s) => $s['type'] !== 'sound')),
            ));

            $podcast->update([
                'status' => RadioPodcastStatus::Published,
                'title' => $script['title'],
                'description' => $script['description'] ?? null,
                'tts_text' => $ttsText,
                'voice_provider' => 'elevenlabs',
                'voice_id' => $voiceId,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'size_bytes' => Storage::disk($disk)->size($path),
                'actual_duration_seconds' => $actualDuration,
                'generation_cost_cents' => $scriptResult['cost_cents'],
                'published_at' => now(),
                'production_preset' => RadioProductionPreset::EmissionCinematic->value,
            ]);

            $this->cache->warmPodcast($podcast);
            $this->refreshRadioPlaylist();

            Log::info('Emission generated successfully', [
                'podcast_id' => $podcast->id,
                'title' => $script['title'],
                'duration' => $actualDuration,
                'sounds' => $sounds->pluck('id')->all(),
            ]);

            return $podcast;
        } catch (\Throwable $e) {
            foreach ($tempFiles as $f) {
                @unlink($f);
            }

            Log::error('Emission generation failed', [
                'podcast_id' => $podcast->id,
                'error' => $e->getMessage(),
            ]);

            $podcast->update([
                'status' => RadioPodcastStatus::Failed,
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            return null;
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Sound>
     */
    private function selectSoundsForEmission(): \Illuminate\Support\Collection
    {
        $minSounds = (int) config('radio.host.emission_min_sounds', 3);
        $maxSounds = (int) config('radio.host.emission_max_sounds', 5);

        return Sound::public()
            ->with(['user', 'category', 'environment', 'soundLocation', 'soundFile', 'soundAnalysis.birdnetDetections'])
            ->whereNotNull('duration')
            ->where('duration', '>=', 30)
            ->orderByDesc('play_count')
            ->limit(30)
            ->get()
            ->filter(fn (Sound $s) => $s->soundFile !== null)
            ->shuffle()
            ->take(random_int($minSounds, $maxSounds));
    }

    private function synthesizeSegment(string $text, string $voiceId): ?string
    {
        return $this->elevenLabs->synthesizeSpeech($text, $voiceId, 'emission_seg');
    }

    private function resolveSoundAudioPath(Sound $sound): ?string
    {
        if (! $this->cache->exists('sounds', $sound->id)) {
            try {
                $this->cache->warmSound($sound);
            } catch (\Throwable) {
                return null;
            }
        }

        $cachedPath = $this->cache->pathFor('sounds', $sound->id);

        if (! file_exists($cachedPath)) {
            return null;
        }

        $tempCopy = sys_get_temp_dir() . '/' . Str::uuid() . '_sound_' . $sound->id . '.mp3';
        copy($cachedPath, $tempCopy);

        return $tempCopy;
    }

    private function refreshRadioPlaylist(): void
    {
        try {
            $path = storage_path('app/radio-cache/playlist.liq');
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents(
                $path,
                app(RadioPlaylistExportService::class)->liq(),
                LOCK_EX
            );
            app(RadioStateService::class)->requestReload();
        } catch (\Throwable $e) {
            Log::warning('Emission generated but radio playlist refresh failed', ['error' => $e->getMessage()]);
        }
    }
}
