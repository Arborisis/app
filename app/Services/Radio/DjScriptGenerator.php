<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioDaypart;
use App\Models\RadioHostPersonality;
use App\Models\Sound;
use App\Services\AI\OpenRouterService;
use App\Services\Radio\Prompts\DjScriptPromptBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Composes context + builds a prompt + calls the LLM to produce ONE
 * short DJ intro. Handles fingerprint anti-repetition with a single retry.
 *
 * Result shape:
 *   array{
 *     text: string,
 *     opening: string,
 *     fingerprint: string,
 *     prompt_hash: string,
 *     model: string,
 *     mentions: array<string, mixed>,
 *     cost_cents: int|null,
 *     retries: int,
 *   }
 */
class DjScriptGenerator
{
    public function __construct(
        private readonly DjScriptPromptBuilder $promptBuilder,
        private readonly HostPersonalitySelector $selector,
        private readonly OpenRouterService $openRouter,
        private readonly RadioHostContextService $context,
        private readonly ?StorytellingEnricher $storytelling = null,
    ) {}

    /**
     * Prepares the prompts and prompt_hash WITHOUT calling the LLM.
     * Useful for cache lookups before paying for an API call.
     *
     * @param  array<string, mixed>|null  $previousSound
     * @return array{prompts: array{system: string, user: string}, prompt_hash: string, recent_fingerprints: array<int, string>}
     */
    public function prepare(
        Sound $sound,
        RadioHostPersonality $personality,
        RadioDaypart $daypart,
        ?array $previousSound = null,
    ): array {
        $soundPayload = $this->safeSoundPayload($sound);
        $storytelling = $this->safeStorytelling($sound, $previousSound);
        $recentOpenings = $this->selector->recentOpeningTexts($personality, 6);
        $fingerprintWindow = (int) config('radio.host.dj_fingerprint_window', 30);

        $prompts = $this->promptBuilder->build(
            sound: $sound,
            personality: $personality,
            daypart: $daypart,
            recentOpenings: $recentOpenings,
            previousSound: $previousSound,
            soundPayload: $soundPayload,
            storytelling: $storytelling,
        );

        return [
            'prompts' => $prompts,
            'prompt_hash' => $this->stableSignature($sound, $personality, $daypart, $previousSound),
            'recent_fingerprints' => $this->selector->recentOpenings($personality, $fingerprintWindow),
        ];
    }

    /**
     * Stable cache signature: independent of recently-used openings so the same
     * (sound, personality, daypart, previous) combo always resolves to the same hash.
     *
     * @param  array<string, mixed>|null  $previousSound
     */
    private function stableSignature(
        Sound $sound,
        RadioHostPersonality $personality,
        RadioDaypart $daypart,
        ?array $previousSound,
    ): string {
        $payload = [
            'sound_id' => $sound->id,
            'personality' => $personality->slug,
            'daypart' => $daypart->value,
            'previous' => $previousSound === null ? null : ($previousSound['id'] ?? null),
        ];

        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param  array<string, mixed>|null  $previousSound
     * @return array<string, mixed>|null
     */
    public function generate(
        Sound $sound,
        RadioHostPersonality $personality,
        RadioDaypart $daypart,
        ?array $previousSound = null,
    ): ?array {
        $prepared = $this->prepare($sound, $personality, $daypart, $previousSound);
        $prompts = $prepared['prompts'];
        $promptHash = $prepared['prompt_hash'];
        $recentFingerprints = $prepared['recent_fingerprints'];

        $temperature = (float) config('radio.host.dj_script_temperature', 0.85);
        $maxTokens = (int) config('radio.host.dj_script_max_tokens', 320);

        $attempt = 0;
        $maxRetries = 1;
        $lastResult = null;

        while ($attempt <= $maxRetries) {
            $result = $this->openRouter->generateDjScript(
                $prompts,
                temperature: $temperature + ($attempt * 0.1),
                maxTokens: $maxTokens,
            );

            if ($result === null) {
                return null;
            }

            $fingerprint = $this->fingerprintFor(
                personality: $personality,
                text: (string) $result['text'],
                opening: (string) ($result['opening'] ?? ''),
            );

            $lastResult = $result + [
                'fingerprint' => $fingerprint,
                'prompt_hash' => $promptHash,
                'retries' => $attempt,
            ];

            if (! in_array($fingerprint, $recentFingerprints, true)) {
                return $lastResult;
            }

            Log::info('Radio DJ script: fingerprint collision, retrying once', [
                'personality' => $personality->slug,
                'fingerprint' => $fingerprint,
                'attempt' => $attempt,
            ]);

            $attempt++;
        }

        // Even on collision after retry, keep the second result; the model still likely
        // produced something different enough at the body level.
        return $lastResult;
    }

    public function fingerprintFor(RadioHostPersonality $personality, string $text, string $opening = ''): string
    {
        $normalized = $opening !== ''
            ? $this->normalizeOpening($opening)
            : $this->selector->openingFromText($text);

        return hash('sha256', $personality->slug.'|'.$normalized);
    }

    private function normalizeOpening(string $opening): string
    {
        $normalized = mb_strtolower(trim($opening));
        $normalized = preg_replace('/[\.\,\;\:\!\?\…]+/u', '', $normalized) ?? $normalized;
        $words = preg_split('/\s+/u', $normalized) ?: [];

        return implode(' ', array_slice($words, 0, 8));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function safeSoundPayload(Sound $sound): ?array
    {
        try {
            // Best-effort: requires sound to be loaded with relations. If a relation is missing,
            // we fall back to no payload rather than blowing up the DJ generation.
            return $this->context->soundPayload($sound);
        } catch (\Throwable $e) {
            Log::info('Radio DJ script: sound payload unavailable, generating without context', [
                'sound_id' => $sound->id,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $previousSound
     * @return array<string, mixed>|null
     */
    private function safeStorytelling(Sound $sound, ?array $previousSound): ?array
    {
        if ($this->storytelling === null || ! (bool) config('radio.host.continuity_enabled', false)) {
            return null;
        }

        try {
            $previous = null;
            if (isset($previousSound['id'])) {
                $previous = Sound::query()->find((int) $previousSound['id']);
            }

            return $this->storytelling->enrich($sound, $previous);
        } catch (\Throwable $e) {
            Log::info('Radio DJ script: storytelling unavailable, generating without enriched context', [
                'sound_id' => $sound->id,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
