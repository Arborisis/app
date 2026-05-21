<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioProductionPreset;

final readonly class RadioProductionPresetConfig
{
    public function __construct(
        public RadioProductionPreset $preset,
        public float $loudnessI,
        public float $loudnessTp,
        public int $loudnessLra,
        public float $sidechainRatio,
        public int $sidechainAttack,
        public int $sidechainRelease,
        public int $voiceHighpassHz,
        public float $musicVolume,
        public float $fieldBedVolume,
        public float $identVolume,
        public int $maxMusicSeconds,
        public float $musicCoverage,
    ) {}

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromArray(RadioProductionPreset $preset, array $values): self
    {
        return new self(
            preset: $preset,
            loudnessI: (float) ($values['loudness_i'] ?? -16.0),
            loudnessTp: (float) ($values['loudness_tp'] ?? -1.5),
            loudnessLra: (int) ($values['loudness_lra'] ?? 11),
            sidechainRatio: (float) ($values['sidechain_ratio'] ?? 10),
            sidechainAttack: (int) ($values['sidechain_attack'] ?? 20),
            sidechainRelease: (int) ($values['sidechain_release'] ?? 650),
            voiceHighpassHz: (int) ($values['voice_highpass_hz'] ?? 70),
            musicVolume: (float) ($values['music_volume'] ?? 0.12),
            fieldBedVolume: (float) ($values['field_bed_volume'] ?? 0.16),
            identVolume: (float) ($values['ident_volume'] ?? 0.22),
            maxMusicSeconds: (int) ($values['max_music_seconds'] ?? 90),
            musicCoverage: (float) ($values['music_coverage'] ?? 0.6),
        );
    }

    public function loudnormFilter(): string
    {
        return sprintf(
            'loudnorm=I=%s:TP=%s:LRA=%d',
            $this->floatForFfmpeg($this->loudnessI),
            $this->floatForFfmpeg($this->loudnessTp),
            $this->loudnessLra,
        );
    }

    private function floatForFfmpeg(float $value): string
    {
        return rtrim(rtrim(sprintf('%.2F', $value), '0'), '.');
    }
}
