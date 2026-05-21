<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use App\Services\AI\ElevenLabsService;
use App\Services\AI\OpenRouterService;
use App\Services\Radio\Filters\SidechainBedFilter;
use App\Services\Radio\Filters\VoiceChainFilter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class RadioAudioProductionService
{
    public function __construct(
        private readonly ElevenLabsService $elevenLabs,
        private readonly OpenRouterService $openRouter,
        private readonly RadioAudioNormalizationService $normalization,
        private readonly ?AudioProductionPresetResolver $presetResolver = null,
        private readonly ?VoiceChainFilter $voiceChainFilter = null,
        private readonly ?SidechainBedFilter $sidechainBedFilter = null,
    ) {}

    public function produceVoiceLedShow(string $voiceFile, ?string $fieldBedFile, array $metadata = []): string
    {
        if (! config('radio.production.enabled', true)) {
            return $this->basicMix($voiceFile, $fieldBedFile);
        }

        $preset = $this->resolvePreset($metadata);
        $duration = max(3.0, $this->normalization->probeDuration($voiceFile) ?? 180.0);
        $generated = $this->generateProductionLayers($metadata, (int) ceil($duration), $preset);

        try {
            return $this->mixLayers($voiceFile, $fieldBedFile, $generated, $duration, $preset);
        } finally {
            $this->unlinkFiles($generated);
        }
    }

    /**
     * @param list<string> $segmentFiles
     */
    public function produceSegmentedShow(array $segmentFiles, array $metadata = []): ?string
    {
        $raw = $this->concatSegments($segmentFiles);

        if (! $raw || ! file_exists($raw)) {
            return null;
        }

        if (! config('radio.production.enabled', true)) {
            return $raw;
        }

        $preset = $this->resolvePreset($metadata);
        $duration = max(3.0, $this->normalization->probeDuration($raw) ?? 600.0);
        $generated = $this->generateProductionLayers($metadata, (int) ceil($duration), $preset);

        try {
            $produced = $this->mixLayers($raw, null, $generated, $duration, $preset);
            @unlink($raw);

            return $produced;
        } finally {
            $this->unlinkFiles($generated);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function generateProductionLayers(array $metadata, int $durationSeconds, RadioProductionPresetConfig $preset): array
    {
        $showType = $metadata['show_type'] ?? 'podcast';
        $theme = trim((string) ($metadata['theme'] ?? $metadata['title'] ?? 'écoute naturaliste Arborisis'));
        $plan = $this->productionPlan($metadata, $durationSeconds);

        $musicDuration = min(
            $preset->maxMusicSeconds,
            (int) ($plan['music_duration_seconds'] ?? max(10, $durationSeconds)),
            max(5, (int) ceil($durationSeconds * $preset->musicCoverage)),
        );

        $music = null;
        if (config('radio.production.music_enabled', true)) {
            $music = $this->elevenLabs->composeMusic(
                (string) ($plan['music_prompt'] ?? $this->musicPrompt($showType, $theme, $durationSeconds)),
                $musicDuration,
                'radio_music_bed'
            );
        }

        $fallbackBed = null;
        if (! $music && config('radio.production.sound_effects_enabled', true)) {
            $fallbackBed = $this->elevenLabs->generateSoundEffect(
                (string) ($plan['ambient_prompt'] ?? $this->ambientPrompt($showType, $theme)),
                min(30.0, max(8.0, $durationSeconds)),
                true,
                'radio_ambient_bed'
            );
        }

        $intro = null;
        $outro = null;
        if (config('radio.production.sound_effects_enabled', true)) {
            $intro = $this->elevenLabs->generateSoundEffect(
                (string) ($plan['intro_fx_prompt'] ?? 'Soft organic radio ident: airy leaves, distant chime, warm cinematic swell, natural and premium, no speech'),
                (float) config('radio.production.ident_seconds', 5),
                false,
                'radio_intro_ident'
            );
            $outro = $this->elevenLabs->generateSoundEffect(
                (string) ($plan['outro_fx_prompt'] ?? 'Elegant closing radio sting: gentle wood resonance, soft breeze, subtle final shimmer, no speech'),
                (float) config('radio.production.ident_seconds', 5),
                false,
                'radio_outro_ident'
            );
        }

        return [
            'music' => $music,
            'fallback_bed' => $fallbackBed,
            'intro' => $intro,
            'outro' => $outro,
            'music_volume' => $plan['music_volume'] ?? $preset->musicVolume,
            'field_bed_volume' => $plan['field_bed_volume'] ?? $preset->fieldBedVolume,
            'ident_volume' => $plan['ident_volume'] ?? $preset->identVolume,
        ];
    }

    private function mixLayers(string $voiceFile, ?string $fieldBedFile, array $generated, float $duration, RadioProductionPresetConfig $preset): string
    {
        $bed = $generated['music'] ?? $generated['fallback_bed'] ?? null;
        $intro = $generated['intro'] ?? null;
        $outro = $generated['outro'] ?? null;

        if (! $bed && ! $fieldBedFile && ! $intro && ! $outro) {
            return $this->basicMix($voiceFile, null);
        }

        $duckedBedCount = (int) ($fieldBedFile && file_exists($fieldBedFile)) + (int) ($bed && file_exists($bed));
        $splitLabels = ['[voice]'];
        for ($i = 1; $i <= $duckedBedCount; $i++) {
            $splitLabels[] = '[voice_sc' . $i . ']';
        }

        $inputs = ['-i', $voiceFile];
        $filterParts = [$this->voiceChain()->build(0, $preset, $splitLabels)];
        $mixLabels = ['[voice]'];
        $inputIndex = 1;
        $sidechainIndex = 1;

        if ($fieldBedFile && file_exists($fieldBedFile)) {
            array_push($inputs, '-stream_loop', '-1', '-i', $fieldBedFile);
            $fieldVolume = (float) ($generated['field_bed_volume'] ?? $preset->fieldBedVolume);
            $filterParts[] = "[{$inputIndex}:a]aformat=sample_rates=44100:channel_layouts=stereo,atrim=0:{$duration},afade=t=in:st=0:d=2,afade=t=out:st=" . max(0, $duration - 4) . ':d=4,volume=' . $fieldVolume . '[fieldraw]';
            $filterParts[] = $this->sidechainFilter()->build('[fieldraw]', '[voice_sc' . $sidechainIndex . ']', '[field]', $preset);
            $mixLabels[] = '[field]';
            $inputIndex++;
            $sidechainIndex++;
        }

        if ($bed && file_exists($bed)) {
            array_push($inputs, '-stream_loop', '-1', '-i', $bed);
            $musicVolume = (float) ($generated['music_volume'] ?? $preset->musicVolume);
            $filterParts[] = "[{$inputIndex}:a]aformat=sample_rates=44100:channel_layouts=stereo,atrim=0:{$duration},afade=t=in:st=0:d=4,afade=t=out:st=" . max(0, $duration - 6) . ':d=6,volume=' . $musicVolume . '[musicraw]';
            $filterParts[] = $this->sidechainFilter()->build('[musicraw]', '[voice_sc' . $sidechainIndex . ']', '[music]', $preset);
            $mixLabels[] = '[music]';
            $inputIndex++;
            $sidechainIndex++;
        }

        if ($intro && file_exists($intro)) {
            $identVolume = (float) ($generated['ident_volume'] ?? $preset->identVolume);
            array_push($inputs, '-i', $intro);
            $filterParts[] = "[{$inputIndex}:a]aformat=sample_rates=44100:channel_layouts=stereo,adelay=0|0,volume={$identVolume}[intro]";
            $mixLabels[] = '[intro]';
            $inputIndex++;
        }

        if ($outro && file_exists($outro)) {
            $delayMs = max(0, (int) (($duration - (float) config('radio.production.ident_seconds', 5)) * 1000));
            $identVolume = (float) ($generated['ident_volume'] ?? $preset->identVolume);
            array_push($inputs, '-i', $outro);
            $filterParts[] = "[{$inputIndex}:a]aformat=sample_rates=44100:channel_layouts=stereo,adelay={$delayMs}|{$delayMs},volume={$identVolume}[outro]";
            $mixLabels[] = '[outro]';
        }

        $filterParts[] = implode('', $mixLabels) . 'amix=inputs=' . count($mixLabels) . ':duration=first:dropout_transition=3,' . $preset->loudnormFilter() . '[aout]';

        $output = sys_get_temp_dir() . '/' . Str::uuid() . '_radio_produced.mp3';
        $process = new Process(array_merge([
            'ffmpeg',
            '-y',
        ], $inputs, [
            '-filter_complex', implode(';', $filterParts),
            '-map', '[aout]',
            '-codec:a', 'libmp3lame',
            '-b:a', '192k',
            '-ar', '44100',
            '-ac', '2',
            $output,
        ]));

        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($output)) {
            Log::error('Radio production mix failed', ['stderr' => $process->getErrorOutput()]);

            return $this->basicMix($voiceFile, $fieldBedFile);
        }

        return $output;
    }

    private function basicMix(string $voiceFile, ?string $backgroundFile): string
    {
        if (! $backgroundFile || ! file_exists($backgroundFile)) {
            $output = sys_get_temp_dir() . '/' . Str::uuid() . '_radio_voice.mp3';
            $this->normalization->normalize($voiceFile, $output);

            return $output;
        }

        $duration = $this->normalization->probeDuration($voiceFile) ?? 180;
        $output = sys_get_temp_dir() . '/' . Str::uuid() . '_radio_basic_mix.mp3';
        $process = new Process([
            'ffmpeg',
            '-y',
            '-i', $voiceFile,
            '-stream_loop', '-1',
            '-i', $backgroundFile,
            '-filter_complex', '[0:a]volume=1.0[a0];[1:a]atrim=0:' . $duration . ',volume=0.20[a1];[a0][a1]amix=inputs=2:duration=first:dropout_transition=2,loudnorm=I=-16:TP=-1.5:LRA=11[aout]',
            '-map', '[aout]',
            '-codec:a', 'libmp3lame',
            '-b:a', '192k',
            '-ar', '44100',
            '-ac', '2',
            $output,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! file_exists($output)) {
            Log::error('Radio basic mix failed', ['stderr' => $process->getErrorOutput()]);
            $this->normalization->normalize($voiceFile, $output);
        }

        return $output;
    }

    /**
     * @param list<string> $segmentFiles
     */
    private function concatSegments(array $segmentFiles): ?string
    {
        $concatListPath = sys_get_temp_dir() . '/' . Str::uuid() . '_radio_concat.txt';
        $concatContent = '';
        foreach ($segmentFiles as $file) {
            $concatContent .= "file '" . str_replace("'", "'\\''", $file) . "'\n";
        }
        file_put_contents($concatListPath, $concatContent);

        $output = sys_get_temp_dir() . '/' . Str::uuid() . '_radio_concat.mp3';
        $process = new Process([
            'ffmpeg',
            '-y',
            '-f', 'concat',
            '-safe', '0',
            '-i', $concatListPath,
            '-codec:a', 'libmp3lame',
            '-b:a', '192k',
            '-ar', '44100',
            '-ac', '2',
            $output,
        ]);
        $process->setTimeout(600);
        $process->run();
        @unlink($concatListPath);

        if (! $process->isSuccessful() || ! file_exists($output)) {
            Log::error('Radio segment concat failed', ['stderr' => $process->getErrorOutput()]);

            return null;
        }

        return $output;
    }

    private function musicPrompt(string $showType, string $theme, int $durationSeconds): string
    {
        $format = $showType === RadioShowType::Emission->value ? 'long-form nature radio show' : 'long-form premium nature podcast';

        return "Instrumental bed for a {$format} about {$theme}. Organic textures, soft piano felt, light marimba, distant field-recording ambience, slow evolving structure, no vocals, no copyrighted style references, leave room for spoken French narration, {$durationSeconds} seconds mood arc.";
    }

    private function ambientPrompt(string $showType, string $theme): string
    {
        return "Seamless looping atmospheric bed for {$showType} about {$theme}: quiet forest air, subtle low warm drone, soft leaves, elegant broadcast texture, no speech, no melody dominating narration.";
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function resolvePreset(array $metadata): RadioProductionPresetConfig
    {
        $explicit = null;
        if (isset($metadata['production_preset'])) {
            $explicit = $metadata['production_preset'] instanceof RadioProductionPreset
                ? $metadata['production_preset']
                : RadioProductionPreset::tryFrom((string) $metadata['production_preset']);
        }

        return ($this->presetResolver ?? app(AudioProductionPresetResolver::class))->resolve(
            showType: $metadata['show_type'] ?? null,
            channel: $metadata['channel'] ?? null,
            explicit: $explicit,
        );
    }

    private function voiceChain(): VoiceChainFilter
    {
        return $this->voiceChainFilter ?? app(VoiceChainFilter::class);
    }

    private function sidechainFilter(): SidechainBedFilter
    {
        return $this->sidechainBedFilter ?? app(SidechainBedFilter::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function productionPlan(array $metadata, int $durationSeconds): array
    {
        if (! config('radio.production.ai_enabled', true)) {
            return [];
        }

        $metadata['duration_seconds'] = $durationSeconds;
        $metadata['requested_model'] = config('radio.production.ai_model', config('radio.host.ai_model', 'anthropic/claude-opus-4.7'));

        return $this->openRouter->generateRadioProductionPlan($metadata) ?? [];
    }

    /**
     * @param array<string, string|null> $files
     */
    private function unlinkFiles(array $files): void
    {
        foreach ($files as $file) {
            if (is_string($file) && $file !== '') {
                @unlink($file);
            }
        }
    }
}
