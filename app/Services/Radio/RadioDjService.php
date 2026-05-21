<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioDaypart;
use App\Models\RadioDjAnnouncement;
use App\Models\RadioHostPersonality;
use App\Models\Sound;
use App\Services\AI\ElevenLabsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RadioDjService
{
    public function __construct(
        private readonly ?HostPersonalitySelector $selector = null,
        private readonly ?DjScriptGenerator $scriptGenerator = null,
        private readonly ?ElevenLabsService $elevenLabs = null,
    ) {}

    /**
     * Generates (or returns the cached) DJ announcement for a given sound.
     *
     * When `radio.host.personalities_enabled` is on, an AI-driven personality
     * crafts a unique script in its own voice. Otherwise, falls back to the
     * legacy fixed template — used as last-resort safety net.
     *
     * @param  array<string, mixed>|null  $previousSound  Optional context to enable continuity transitions.
     */
    public function announcementFor(
        Sound $sound,
        ?RadioHostPersonality $personality = null,
        ?array $previousSound = null,
    ): ?RadioDjAnnouncement {
        if (! config('radio.dj.enabled', true)) {
            return null;
        }

        if ($this->personalitiesEnabled()) {
            $announcement = $this->generateWithPersonality($sound, $personality, $previousSound);

            if ($announcement !== null) {
                return $announcement;
            }

            Log::info('Radio DJ: personality flow returned null, falling back to legacy template', [
                'sound_id' => $sound->id,
            ]);
        }

        return $this->generateWithLegacyTemplate($sound);
    }

    public function legacyTemplate(Sound $sound): string
    {
        $creator = $sound->user?->name ?? 'la communauté Arborisis';
        $title = trim($sound->title);

        $text = "Vous écoutez {$title}, une immersion sonore proposée par {$creator}, sur Arborisis Radio.";

        return Str::limit(preg_replace('/\s+/', ' ', $text) ?: $text, 220, '');
    }

    /**
     * Build short payload for continuity context. Useful when callers want to
     * inject "previous track" hints into the next announcement.
     *
     * @return array{title: string, creator: string}
     */
    public function continuityHint(Sound $previous): array
    {
        return [
            'title' => trim((string) $previous->title),
            'creator' => $previous->user?->name ?? 'la communauté Arborisis',
        ];
    }

    private function personalitiesEnabled(): bool
    {
        return (bool) config('radio.host.personalities_enabled', false)
            && $this->selector !== null
            && $this->scriptGenerator !== null
            && $this->elevenLabs !== null;
    }

    /**
     * @param  array<string, mixed>|null  $previousSound
     */
    private function generateWithPersonality(
        Sound $sound,
        ?RadioHostPersonality $personality,
        ?array $previousSound,
    ): ?RadioDjAnnouncement {
        $daypart = RadioDaypart::fromCarbon(now());

        $personality ??= $this->selector?->select('dj', $daypart);
        if ($personality === null || $personality->voice_id === null || $personality->voice_id === '') {
            Log::info('Radio DJ: no suitable personality with a configured voice_id', [
                'sound_id' => $sound->id,
            ]);

            return null;
        }

        // Cheap pre-check: same prompts → same hash → reuse existing audio if any.
        $prepared = $this->scriptGenerator?->prepare(
            sound: $sound,
            personality: $personality,
            daypart: $daypart,
            previousSound: $previousSound,
        );

        if ($prepared !== null) {
            $existingByHash = RadioDjAnnouncement::query()
                ->where('voice_provider', $personality->voice_provider)
                ->where('voice_id', $personality->voice_id)
                ->where('prompt_hash', $prepared['prompt_hash'])
                ->first();

            if ($existingByHash && $this->fileStillReadable($existingByHash)) {
                return $existingByHash;
            }
        }

        $script = $this->scriptGenerator?->generate(
            sound: $sound,
            personality: $personality,
            daypart: $daypart,
            previousSound: $previousSound,
        );

        if ($script === null || trim((string) ($script['text'] ?? '')) === '') {
            return null;
        }

        $text = trim((string) $script['text']);
        $fingerprint = (string) ($script['fingerprint'] ?? '');
        $promptHash = (string) ($script['prompt_hash'] ?? '');

        $existing = RadioDjAnnouncement::query()
            ->where('voice_provider', $personality->voice_provider)
            ->where('voice_id', $personality->voice_id)
            ->where('prompt_hash', $promptHash)
            ->first();

        if ($existing && $this->fileStillReadable($existing)) {
            return $existing;
        }

        $voiceSettings = $personality->voiceSettingsArray();

        $tempFile = $this->elevenLabs?->synthesizeSpeechWithSettings(
            text: $text,
            voiceId: $personality->voice_id,
            voiceSettings: $voiceSettings,
            suffix: 'dj_'.$personality->slug.'_'.$sound->id,
        );

        if (! $tempFile || ! file_exists($tempFile)) {
            Log::warning('Radio DJ: ElevenLabs returned no audio', [
                'sound_id' => $sound->id,
                'personality' => $personality->slug,
            ]);

            return null;
        }

        try {
            $disk = (string) config('filesystems.audio_disk', 'audio');
            $path = 'radio/dj/'.$personality->slug.'/'.Str::uuid().'_'.$sound->id.'.mp3';
            $body = file_get_contents($tempFile);

            if ($body === false) {
                Log::warning('Radio DJ: could not read synthesized temp file', ['path' => $tempFile]);

                return null;
            }

            Storage::disk($disk)->put($path, $body);

            return RadioDjAnnouncement::query()->create([
                'sound_id' => $sound->id,
                'previous_sound_id' => $previousSound['id'] ?? null,
                'voice_provider' => $personality->voice_provider,
                'voice_id' => $personality->voice_id,
                'personality_slug' => $personality->slug,
                'text' => $text,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'text_hash' => hash('sha256', $personality->voice_provider.'|'.$personality->voice_id.'|'.$text),
                'prompt_hash' => $promptHash,
                'phrase_fingerprint' => $fingerprint,
                'daypart' => $daypart->value,
                'generated_at' => now(),
            ]);
        } finally {
            @unlink($tempFile);
        }
    }

    private function generateWithLegacyTemplate(Sound $sound): ?RadioDjAnnouncement
    {
        $text = $this->legacyTemplate($sound);
        $voiceProvider = (string) config('radio.dj.voice_provider', 'elevenlabs');
        $voiceId = (string) (config('radio.dj.elevenlabs_voice_id') ?: config('radio.dj.voice_id'));
        $hash = hash('sha256', $voiceProvider.'|'.$voiceId.'|'.$text);

        $existing = RadioDjAnnouncement::query()
            ->where('voice_provider', $voiceProvider)
            ->where('voice_id', $voiceId)
            ->where('text_hash', $hash)
            ->first();

        if ($existing && $this->fileStillReadable($existing)) {
            return $existing;
        }

        if ($voiceProvider !== 'elevenlabs') {
            return null;
        }

        return $this->generateWithElevenLabs($sound, $text, $hash, $voiceId);
    }

    private function fileStillReadable(RadioDjAnnouncement $announcement): bool
    {
        try {
            return Storage::disk($announcement->disk)->exists($announcement->path);
        } catch (\Throwable) {
            return false;
        }
    }

    private function generateWithElevenLabs(Sound $sound, string $text, string $hash, ?string $voiceId): ?RadioDjAnnouncement
    {
        $apiKey = config('services.elevenlabs.api_key');
        $voiceId ??= config('services.elevenlabs.voice_id');

        if (empty($apiKey) || empty($voiceId)) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'xi-api-key' => $apiKey,
                'Accept' => 'audio/mpeg',
            ])->timeout(30)->post(
                rtrim((string) config('services.elevenlabs.base_url', 'https://api.elevenlabs.io/v1'), '/')."/text-to-speech/{$voiceId}",
                [
                    'text' => $text,
                    'model_id' => config('services.elevenlabs.model', 'eleven_multilingual_v2'),
                    'voice_settings' => [
                        'stability' => (float) config('services.elevenlabs.stability', 0.55),
                        'similarity_boost' => (float) config('services.elevenlabs.similarity_boost', 0.75),
                    ],
                ]
            );

            if (! $response->successful()) {
                Log::warning('Radio DJ ElevenLabs generation failed', [
                    'sound_id' => $sound->id,
                    'status' => $response->status(),
                ]);

                return null;
            }

            $disk = config('filesystems.audio_disk', 'audio');
            $path = 'radio/dj/'.Str::uuid().'_'.$sound->id.'.mp3';
            Storage::disk($disk)->put($path, $response->body());

            return RadioDjAnnouncement::query()->create([
                'sound_id' => $sound->id,
                'voice_provider' => 'elevenlabs',
                'voice_id' => $voiceId,
                'text' => $text,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => 'audio/mpeg',
                'text_hash' => $hash,
                'generated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Radio DJ ElevenLabs exception', [
                'sound_id' => $sound->id,
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
