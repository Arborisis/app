<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\CircuitBreaker;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ElevenLabsService
{
    private const DEFAULT_BASE_URL = 'https://api.elevenlabs.io/v1';

    private const DEFAULT_TTS_MODEL = 'eleven_multilingual_v2';

    private const DEFAULT_SOUND_MODEL = 'eleven_text_to_sound_v2';

    private const DEFAULT_MUSIC_MODEL = 'music_v1';

    /**
     * Synthesizes each text chunk separately, returning one temp file per chunk.
     * Returns an empty array (not null) so callers can detect partial success.
     *
     * @param  list<string>  $texts
     * @return list<string> Temp file paths — caller must delete them
     */
    public function synthesizeSpeechBatch(array $texts, ?string $voiceId = null, string $suffix = 'voice'): array
    {
        $results = [];

        foreach ($texts as $i => $text) {
            $text = trim($text);
            if ($text === '') {
                continue;
            }

            $file = $this->synthesizeSpeech($text, $voiceId, $suffix.'_'.$i);

            if (! $file) {
                Log::warning('ElevenLabs batch: chunk synthesis failed, aborting batch', ['chunk_index' => $i]);
                foreach ($results as $f) {
                    @unlink($f);
                }

                return [];
            }

            $results[] = $file;
        }

        return $results;
    }

    /**
     * Splits a text into speech-friendly chunks at sentence boundaries.
     *
     * @return list<string>
     */
    public static function splitTextIntoChunks(string $text, int $maxWordsPerChunk = 180): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }

        // Split at sentence boundaries
        $sentences = preg_split('/(?<=[.?!…])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];

        $chunks = [];
        $current = '';
        $currentWords = 0;

        foreach ($sentences as $sentence) {
            $words = str_word_count($sentence);

            if ($currentWords > 0 && $currentWords + $words > $maxWordsPerChunk) {
                $chunks[] = trim($current);
                $current = $sentence;
                $currentWords = $words;
            } else {
                $current .= ($current !== '' ? ' ' : '').$sentence;
                $currentWords += $words;
            }
        }

        if ($current !== '') {
            $chunks[] = trim($current);
        }

        return array_values(array_filter($chunks));
    }

    public function synthesizeSpeech(string $text, ?string $voiceId = null, ?string $suffix = null): ?string
    {
        return $this->synthesizeSpeechWithSettings($text, $voiceId, $this->defaultVoiceSettings(), $suffix);
    }

    /**
     * Generates speech with explicit voice_settings (used by per-personality DJs).
     * Falls back to the global defaults for any missing key.
     *
     * @param  array<string, mixed>  $voiceSettings
     */
    public function synthesizeSpeechWithSettings(
        string $text,
        ?string $voiceId,
        array $voiceSettings,
        ?string $suffix = null,
        ?string $modelId = null,
    ): ?string {
        $apiKey = config('services.elevenlabs.api_key');
        $voiceId ??= config('services.elevenlabs.voice_id');

        if (empty($apiKey) || empty($voiceId)) {
            return null;
        }

        $mergedSettings = array_merge($this->defaultVoiceSettings(), $voiceSettings);
        $mergedSettings = $this->normalizeVoiceSettings($mergedSettings);

        $response = $this->postWithBreaker($this->url("/text-to-speech/{$voiceId}/stream", [
                'output_format' => config('services.elevenlabs.output_format', 'mp3_44100_192'),
            ]), [
                'text' => $text,
                'model_id' => $modelId ?? (string) config('services.elevenlabs.model', self::DEFAULT_TTS_MODEL),
                'voice_settings' => $mergedSettings,
            ], (int) config('services.elevenlabs.timeout', 120));

        if ($response === null) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('ElevenLabs TTS failed', [
                'status' => $response->status(),
                'voice_id' => $voiceId,
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        return $this->writeTempFile($response->body(), $suffix ?? 'elevenlabs_voice');
    }

    /**
     * @return array<string, float|bool>
     */
    private function defaultVoiceSettings(): array
    {
        return [
            'stability' => (float) config('services.elevenlabs.stability', 0.55),
            'similarity_boost' => (float) config('services.elevenlabs.similarity_boost', 0.75),
            'style' => (float) config('services.elevenlabs.style', 0.2),
            'use_speaker_boost' => (bool) config('services.elevenlabs.use_speaker_boost', true),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, float|bool>
     */
    private function normalizeVoiceSettings(array $settings): array
    {
        return [
            'stability' => max(0.0, min(1.0, (float) $settings['stability'])),
            'similarity_boost' => max(0.0, min(1.0, (float) $settings['similarity_boost'])),
            'style' => max(0.0, min(1.0, (float) $settings['style'])),
            'use_speaker_boost' => (bool) $settings['use_speaker_boost'],
        ];
    }

    public function generateSoundEffect(string $prompt, float $durationSeconds, bool $loop = false, ?string $suffix = null): ?string
    {
        $apiKey = config('services.elevenlabs.api_key');

        if (empty($apiKey)) {
            return null;
        }

        $durationSeconds = max(0.5, min(30.0, $durationSeconds));

        $response = $this->postWithBreaker($this->url('/sound-generation', [
                'output_format' => config('services.elevenlabs.sound_output_format', 'mp3_44100_192'),
            ]), [
                'text' => $prompt,
                'duration_seconds' => $durationSeconds,
                'loop' => $loop,
                'prompt_influence' => (float) config('services.elevenlabs.sound_prompt_influence', 0.45),
                'model_id' => config('services.elevenlabs.sound_model', self::DEFAULT_SOUND_MODEL),
            ], (int) config('services.elevenlabs.sound_timeout', 120));

        if ($response === null) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('ElevenLabs sound effect generation failed', [
                'status' => $response->status(),
                'prompt' => Str::limit($prompt, 180),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        return $this->writeTempFile($response->body(), $suffix ?? 'elevenlabs_fx');
    }

    public function composeMusic(string $prompt, int $durationSeconds, ?string $suffix = null): ?string
    {
        $apiKey = config('services.elevenlabs.api_key');

        if (empty($apiKey) || ! config('radio.production.music_enabled', true)) {
            return null;
        }

        $durationMs = max(3000, min(600000, $durationSeconds * 1000));

        $response = $this->postWithBreaker($this->url('/music', [
                'output_format' => config('services.elevenlabs.music_output_format', 'mp3_44100_192'),
            ]), [
                'prompt' => $prompt,
                'music_length_ms' => $durationMs,
                'model_id' => config('services.elevenlabs.music_model', self::DEFAULT_MUSIC_MODEL),
                'force_instrumental' => true,
            ], (int) config('services.elevenlabs.music_timeout', 300));

        if ($response === null) {
            return null;
        }

        if (! $response->successful()) {
            Log::warning('ElevenLabs music composition failed', [
                'status' => $response->status(),
                'prompt' => Str::limit($prompt, 180),
                'body' => Str::limit($response->body(), 500),
            ]);

            return null;
        }

        return $this->writeTempFile($response->body(), $suffix ?? 'elevenlabs_music');
    }

    /**
     * @return array<string, string>
     */
    private function audioHeaders(): array
    {
        return [
            'xi-api-key' => (string) config('services.elevenlabs.api_key'),
            'Accept' => 'audio/mpeg',
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWithBreaker(string $url, array $payload, int $timeoutSeconds): ?Response
    {
        return app(CircuitBreaker::class)->attempt(
            'elevenlabs',
            fn () => Http::withHeaders($this->audioHeaders())
                ->timeout($timeoutSeconds)
                ->post($url, $payload),
            null,
        );
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    private function url(string $path, array $query = []): string
    {
        $url = rtrim((string) config('services.elevenlabs.base_url', self::DEFAULT_BASE_URL), '/').$path;
        $query = array_filter($query, fn ($value) => $value !== null && $value !== '');

        return empty($query) ? $url : $url.'?'.http_build_query($query);
    }

    private function writeTempFile(string $body, string $suffix): ?string
    {
        if ($body === '') {
            return null;
        }

        $path = sys_get_temp_dir().'/'.Str::uuid().'_'.preg_replace('/[^a-z0-9_]+/i', '_', $suffix).'.mp3';
        file_put_contents($path, $body);

        return file_exists($path) && filesize($path) > 0 ? $path : null;
    }
}
