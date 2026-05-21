<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioProductionPreset;
use App\Models\RadioPodcast;
use App\Models\Sound;
use App\Services\AI\ElevenLabsService;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RadioPodcastGenerationService
{
    public function __construct(
        private readonly OpenRouterService $openRouter,
        private readonly ElevenLabsService $elevenLabs,
        private readonly PodcastIdeaGeneratorService $ideaGenerator,
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
            ->count();

        return $pendingCount < 3;
    }

    public function generate(): ?RadioPodcast
    {
        if (! $this->canGenerate()) {
            Log::info('Podcast generation skipped: pipeline busy or missing keys');

            return null;
        }

        $podcast = RadioPodcast::query()->create([
            'status' => RadioPodcastStatus::Pending,
            'title' => 'Generating...',
            'target_duration_seconds' => (int) round(
                ((int) config('radio.podcast.min_duration', 600) + (int) config('radio.podcast.max_duration', 1200)) / 2
            ),
        ]);

        try {
            $podcast->update(['status' => RadioPodcastStatus::Generating]);

            // 1. Génération d'idée + recherche web
            $topic = $this->ideaGenerator->generateWeeklyTopic();

            if (! $topic) {
                throw new \RuntimeException('Failed to generate weekly topic with web research');
            }

            $podcast->update([
                'theme' => $topic['theme'],
                'research_json' => [
                    'angle' => $topic['angle'],
                    'research_summary' => $topic['research_summary'],
                    'keywords' => $topic['keywords'],
                ],
            ]);

            // 2. Sélection des sons
            $sounds = $this->selectSounds();

            if ($sounds->isEmpty()) {
                throw new \RuntimeException('No suitable sounds found for podcast');
            }

            // 3. Génération du script enrichi par la recherche
            $scriptResult = $this->openRouter->generatePodcastScript($sounds->all(), $topic);

            if (! $scriptResult) {
                throw new \RuntimeException('OpenRouter script generation failed');
            }

            $script = $scriptResult['script'];
            $estimatedDuration = (int) $script['estimated_duration_seconds'];
            $minDuration = (int) config('radio.podcast.min_duration', 600);
            $maxDuration = (int) config('radio.podcast.max_duration', 1200);

            if ($estimatedDuration < $minDuration || $estimatedDuration > $maxDuration) {
                throw new \RuntimeException("Estimated duration {$estimatedDuration}s out of bounds [{$minDuration}, {$maxDuration}]");
            }

            // 4. Synthèse vocale ElevenLabs — segment par segment pour éviter la dérive
            $ttsText = $this->buildTtsText($script['segments']); // conservé pour stockage DB
            $voiceFile = $this->synthesizeVoiceSegmented($script['segments']);

            if (! $voiceFile || ! file_exists($voiceFile)) {
                throw new \RuntimeException('Voice synthesis failed');
            }

            // 5. Fond sonore
            $backgroundFile = $this->buildBackgroundTrack($script['segments'], $sounds, $voiceFile);

            // 6. Production audio : lit sonore, FX, musique ElevenLabs et mixage voix
            $assembledFile = $this->production->produceVoiceLedShow($voiceFile, $backgroundFile, [
                'show_type' => 'podcast',
                'production_preset' => RadioProductionPreset::PodcastIntimate->value,
                'title' => $script['title'] ?? null,
                'theme' => $topic['theme'] ?? null,
                'description' => $script['description'] ?? null,
                'script' => $script,
                'research' => $topic,
                'selected_sounds' => $sounds->map(fn (Sound $sound) => [
                    'id' => $sound->id,
                    'title' => $sound->title,
                    'category' => $sound->category?->name,
                    'environment' => $sound->environment?->name,
                    'duration_seconds' => $sound->duration,
                ])->values()->all(),
            ]);

            @unlink($voiceFile);
            if ($backgroundFile && $backgroundFile !== $voiceFile) {
                @unlink($backgroundFile);
            }

            if (! $assembledFile || ! file_exists($assembledFile)) {
                throw new \RuntimeException('Podcast assembly failed');
            }

            // 7. Validation durée
            $actualDuration = $this->normalization->probeDuration($assembledFile);

            if ($actualDuration === null || $actualDuration < $minDuration || $actualDuration > $maxDuration) {
                @unlink($assembledFile);
                throw new \RuntimeException("Actual duration {$actualDuration}s out of bounds [{$minDuration}, {$maxDuration}]");
            }

            // 8. Normalisation
            $normalizedFile = $assembledFile.'_norm.mp3';
            $normSuccess = $this->normalization->normalize($assembledFile, $normalizedFile);
            @unlink($assembledFile);

            if (! $normSuccess || ! file_exists($normalizedFile)) {
                throw new \RuntimeException('Normalization failed');
            }

            // 9. Stockage
            $disk = config('radio.audio_cache.disk', 'radio_cache');
            $path = 'podcasts/'.$podcast->id.'.mp3';
            $stream = fopen($normalizedFile, 'r');
            Storage::disk($disk)->put($path, $stream);
            fclose($stream);
            @unlink($normalizedFile);

            $totalCost = ($topic['cost_cents'] ?? 0) + ($scriptResult['cost_cents'] ?? 0);

            $podcast->update([
                'status' => RadioPodcastStatus::Published,
                'title' => $script['title'],
                'description' => $script['description'],
                'script_json' => $script,
                'tts_text' => $ttsText,
                'voice_provider' => 'elevenlabs',
                'voice_id' => config('radio.podcast.voice_id') ?? config('services.elevenlabs.voice_id'),
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'size_bytes' => Storage::disk($disk)->size($path),
                'actual_duration_seconds' => $actualDuration,
                'sound_ids' => $sounds->pluck('id')->all(),
                'generation_cost_cents' => $totalCost > 0 ? $totalCost : null,
                'published_at' => now(),
                'production_preset' => RadioProductionPreset::PodcastIntimate->value,
            ]);

            $this->cache->warmPodcast($podcast);
            $this->refreshRadioPlaylist();

            Log::info('Podcast generated successfully', [
                'podcast_id' => $podcast->id,
                'theme' => $topic['theme'],
                'title' => $script['title'],
                'duration' => $actualDuration,
            ]);

            return $podcast;
        } catch (\Throwable $e) {
            Log::error('Podcast generation failed', [
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
     * @return Collection<int, Sound>
     */
    private function selectSounds(): Collection
    {
        return Sound::public()
            ->with(['user', 'category', 'environment', 'soundLocation', 'soundFile', 'soundAnalysis.birdnetDetections'])
            ->whereNotNull('duration')
            ->where('duration', '>=', 30)
            ->orderByDesc('play_count')
            ->limit(20)
            ->get()
            ->filter(fn (Sound $s) => $s->soundFile !== null)
            ->shuffle()
            ->take($this->podcastSoundLimit());
    }

    private function podcastSoundLimit(): int
    {
        $min = max(1, (int) config('radio.podcast.min_sounds', 4));
        $max = max($min, (int) config('radio.podcast.max_sounds', 7));

        return random_int($min, $max);
    }

    private function buildTtsText(array $segments): string
    {
        $parts = [];
        foreach ($segments as $segment) {
            if (! empty($segment['text'])) {
                $parts[] = $segment['text'];
            }
        }

        return implode("\n\n", $parts);
    }

    /**
     * Synthesizes each script segment independently to prevent voice drift on long content.
     * Segments are concatenated in order after synthesis.
     */
    private function synthesizeVoiceSegmented(array $segments): ?string
    {
        $voiceId = config('radio.podcast.voice_id') ?? config('services.elevenlabs.voice_id');

        $texts = array_values(array_filter(
            array_map(fn ($s) => isset($s['text']) ? trim((string) $s['text']) : '', $segments),
            fn ($t) => $t !== ''
        ));

        if (empty($texts)) {
            return null;
        }

        $parts = $this->elevenLabs->synthesizeSpeechBatch($texts, $voiceId, 'podcast_seg');

        if (empty($parts)) {
            return null;
        }

        $voice = $this->normalization->concatAudioFiles($parts);

        foreach ($parts as $part) {
            @unlink($part);
        }

        return $voice;
    }

    private function buildBackgroundTrack(array $segments, Collection $sounds, string $voiceFile): ?string
    {
        $extracts = [];
        $voiceDuration = $this->normalization->probeDuration($voiceFile) ?? 180;

        foreach ($segments as $segment) {
            if (empty($segment['sound_id']) || empty($segment['transition_duration_seconds'])) {
                continue;
            }

            $sound = $sounds->first(fn (Sound $s) => $s->id === (int) $segment['sound_id']);
            if (! $sound || ! $sound->soundFile) {
                continue;
            }

            $extract = $this->extractSoundFragment(
                $sound,
                (int) $segment['transition_duration_seconds']
            );

            if ($extract) {
                $extracts[] = $extract;
            }
        }

        if (empty($extracts)) {
            return null;
        }

        $concatList = sys_get_temp_dir().'/'.Str::uuid().'_concat.txt';
        $concatContent = '';
        foreach ($extracts as $extract) {
            $concatContent .= "file '".str_replace("'", "'\\''", $extract)."'\n";
        }
        file_put_contents($concatList, $concatContent);

        $output = sys_get_temp_dir().'/'.Str::uuid().'_bg.mp3';
        $process = new Process([
            'ffmpeg',
            '-y',
            '-f', 'concat',
            '-safe', '0',
            '-i', $concatList,
            '-codec:a', 'libmp3lame',
            '-b:a', '192k',
            '-ar', '44100',
            '-ac', '2',
            $output,
        ]);
        $process->setTimeout(120);
        $process->run();
        @unlink($concatList);

        foreach ($extracts as $extract) {
            @unlink($extract);
        }

        if (! $process->isSuccessful() || ! file_exists($output)) {
            return null;
        }

        $bgDuration = $this->normalization->probeDuration($output) ?? 0;
        if ($bgDuration < $voiceDuration) {
            $looped = sys_get_temp_dir().'/'.Str::uuid().'_bg_loop.mp3';
            $loopProcess = new Process([
                'ffmpeg',
                '-y',
                '-stream_loop', '-1',
                '-i', $output,
                '-t', (string) $voiceDuration,
                '-codec:a', 'libmp3lame',
                '-b:a', '192k',
                '-ar', '44100',
                '-ac', '2',
                $looped,
            ]);
            $loopProcess->setTimeout(120);
            $loopProcess->run();
            @unlink($output);

            if ($loopProcess->isSuccessful() && file_exists($looped)) {
                return $looped;
            }

            return null;
        }

        return $output;
    }

    private function extractSoundFragment(Sound $sound, int $durationSeconds): ?string
    {
        $soundFile = $sound->soundFile;
        if (! $soundFile) {
            return null;
        }

        $disk = $soundFile->disk;
        $sourcePath = $soundFile->radio_path && Storage::disk($disk)->exists($soundFile->radio_path)
            ? $soundFile->radio_path
            : ($soundFile->mime_type === 'audio/mpeg' && Storage::disk($disk)->exists($soundFile->path)
                ? $soundFile->path
                : null);

        if (! $sourcePath) {
            return null;
        }

        $stream = Storage::disk($disk)->readStream($sourcePath);
        if (! $stream) {
            return null;
        }

        $tempOriginal = sys_get_temp_dir().'/'.Str::uuid().'_'.basename($sourcePath);
        file_put_contents($tempOriginal, $stream);
        fclose($stream);

        $soundDuration = $sound->duration ?? $this->normalization->probeDuration($tempOriginal);
        if (! $soundDuration || $soundDuration <= 0) {
            @unlink($tempOriginal);

            return null;
        }

        $start = max(0, min((int) ($soundDuration / 3), (int) ($soundDuration - $durationSeconds)));
        $output = sys_get_temp_dir().'/'.Str::uuid().'_extract.mp3';

        $process = new Process([
            'ffmpeg',
            '-y',
            '-i', $tempOriginal,
            '-ss', (string) $start,
            '-t', (string) $durationSeconds,
            '-codec:a', 'libmp3lame',
            '-b:a', '192k',
            '-ar', '44100',
            '-ac', '2',
            $output,
        ]);
        $process->setTimeout(120);
        $process->run();
        @unlink($tempOriginal);

        if (! $process->isSuccessful() || ! file_exists($output)) {
            return null;
        }

        return $output;
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
            Log::warning('Podcast generated but radio playlist refresh failed', ['error' => $e->getMessage()]);
        }
    }
}
