<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use App\Models\RadioPodcast;
use App\Services\AI\ElevenLabsService;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RadioFlashGenerationService
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
            ->where('show_type', RadioShowType::Flash->value)
            ->count();

        return $pendingCount < 2;
    }

    public function generate(): ?RadioPodcast
    {
        if (! $this->canGenerate()) {
            Log::info('Flash generation skipped: pipeline busy or missing keys');

            return null;
        }

        $minDuration = (int) config('radio.host.flash_min_duration', 90);
        $maxDuration = (int) config('radio.host.flash_max_duration', 180);

        $podcast = RadioPodcast::query()->create([
            'show_type' => RadioShowType::Flash->value,
            'status' => RadioPodcastStatus::Pending,
            'title' => 'Generating flash...',
            'target_duration_seconds' => $minDuration + 30,
        ]);

        try {
            $context = $this->contextService->gather();

            $podcast->update([
                'context_json' => $context,
                'status' => RadioPodcastStatus::Generating,
            ]);

            $scriptResult = $this->openRouter->generateFlashScript($context);

            if (! $scriptResult) {
                throw new \RuntimeException('OpenRouter flash script generation failed');
            }

            $script = $scriptResult['script'];
            $estimatedDuration = (int) $script['estimated_duration_seconds'];

            if ($estimatedDuration < $minDuration || $estimatedDuration > $maxDuration) {
                throw new \RuntimeException("Estimated flash duration {$estimatedDuration}s out of bounds [{$minDuration}, {$maxDuration}]");
            }

            $voiceFile = $this->synthesizeVoice($script);

            if (! $voiceFile || ! file_exists($voiceFile)) {
                throw new \RuntimeException('Flash voice synthesis failed');
            }

            $producedFile = $this->production->produceVoiceLedShow($voiceFile, null, [
                'show_type' => RadioShowType::Flash->value,
                'production_preset' => RadioProductionPreset::FlashPunchy->value,
                'title' => $script['title'] ?? null,
                'theme' => $script['description'] ?? null,
                'description' => $script['description'] ?? null,
                'script' => $script,
                'context' => $context,
            ]);
            @unlink($voiceFile);

            $actualDuration = $this->normalization->probeDuration($producedFile);
            $actualMinDuration = max(60, $minDuration - 20);
            $actualMaxDuration = $maxDuration + 60;

            if ($actualDuration === null || $actualDuration < $actualMinDuration || $actualDuration > $actualMaxDuration) {
                @unlink($producedFile);
                throw new \RuntimeException("Actual flash duration {$actualDuration}s out of bounds [{$actualMinDuration}, {$actualMaxDuration}]");
            }

            $normalizedFile = $producedFile.'_norm.mp3';
            $normSuccess = $this->normalization->normalize($producedFile, $normalizedFile);
            @unlink($producedFile);

            if (! $normSuccess || ! file_exists($normalizedFile)) {
                throw new \RuntimeException('Flash normalization failed');
            }

            $disk = config('radio.host.storage_disk', 'r2');
            $path = 'radio/podcasts/'.$podcast->id.'.mp3';
            $stream = fopen($normalizedFile, 'r');
            Storage::disk($disk)->put($path, $stream);
            fclose($stream);
            @unlink($normalizedFile);

            $voiceId = config('radio.host.host_voice_id')
                ?? config('radio.podcast.voice_id')
                ?? config('services.elevenlabs.voice_id');

            $podcast->update([
                'status' => RadioPodcastStatus::Published,
                'title' => $script['title'],
                'description' => $script['description'] ?? null,
                'tts_text' => $script['text'],
                'voice_provider' => 'elevenlabs',
                'voice_id' => $voiceId,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'size_bytes' => Storage::disk($disk)->size($path),
                'actual_duration_seconds' => $actualDuration,
                'generation_cost_cents' => $scriptResult['cost_cents'],
                'published_at' => now(),
                'production_preset' => RadioProductionPreset::FlashPunchy->value,
            ]);

            $this->cache->warmPodcast($podcast);
            $this->refreshRadioPlaylist();

            Log::info('Flash generated successfully', [
                'podcast_id' => $podcast->id,
                'title' => $script['title'],
                'duration' => $actualDuration,
            ]);

            return $podcast;
        } catch (\Throwable $e) {
            Log::error('Flash generation failed', [
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
     * Synthesizes flash text in segments to prevent voice drift.
     * Uses script paragraphs when available (from updated prompt schema);
     * otherwise falls back to auto-splitting at sentence boundaries.
     *
     * @param  array<string, mixed>  $script
     */
    private function synthesizeVoice(array $script): ?string
    {
        $voiceId = config('radio.host.host_voice_id')
            ?? config('radio.podcast.voice_id')
            ?? config('services.elevenlabs.voice_id');

        // Use structured paragraphs if the script provides them
        $paragraphs = isset($script['paragraphs']) && is_array($script['paragraphs'])
            ? array_values(array_filter(array_map('trim', $script['paragraphs'])))
            : [];

        // Fall back to auto-splitting the full text blob
        if (empty($paragraphs) && ! empty($script['text'])) {
            $paragraphs = ElevenLabsService::splitTextIntoChunks($script['text'], 180);
        }

        if (empty($paragraphs)) {
            return null;
        }

        // Single paragraph: bypass concat overhead
        if (count($paragraphs) === 1) {
            return $this->elevenLabs->synthesizeSpeech($paragraphs[0], $voiceId, 'flash_voice');
        }

        $parts = $this->elevenLabs->synthesizeSpeechBatch($paragraphs, $voiceId, 'flash_voice');

        if (empty($parts)) {
            Log::warning('Flash segmented synthesis returned no parts');

            return null;
        }

        $voiceFile = $this->normalization->concatAudioFiles($parts);

        foreach ($parts as $part) {
            @unlink($part);
        }

        return $voiceFile;
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
            Log::warning('Flash generated but radio playlist refresh failed', ['error' => $e->getMessage()]);
        }
    }
}
