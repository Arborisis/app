<?php

declare(strict_types=1);

use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use App\Models\RadioChannel;
use App\Services\Radio\AudioProductionPresetResolver;
use App\Services\Radio\Filters\SidechainBedFilter;
use App\Services\Radio\Filters\VoiceChainFilter;

it('resolves the default preset from the show type', function () {
    $resolver = new AudioProductionPresetResolver();

    $flash = $resolver->resolve(RadioShowType::Flash->value);
    $emission = $resolver->resolve(RadioShowType::Emission);

    expect($flash->preset)->toBe(RadioProductionPreset::FlashPunchy);
    expect($flash->loudnessI)->toBe(-14.0);
    expect($flash->loudnessLra)->toBe(7);
    expect($emission->preset)->toBe(RadioProductionPreset::EmissionCinematic);
});

it('prefers explicit presets over channel and show type defaults', function () {
    $resolver = new AudioProductionPresetResolver();
    $channel = new RadioChannel([
        'production_preset' => RadioProductionPreset::Interlude,
    ]);

    $resolved = $resolver->resolve(
        showType: RadioShowType::Flash->value,
        channel: $channel,
        explicit: RadioProductionPreset::PodcastIntimate,
    );

    expect($resolved->preset)->toBe(RadioProductionPreset::PodcastIntimate);
});

it('uses channel preset before show type default', function () {
    $resolver = new AudioProductionPresetResolver();
    $channel = new RadioChannel([
        'production_preset' => RadioProductionPreset::Interlude,
    ]);

    $resolved = $resolver->resolve(RadioShowType::Flash->value, $channel);

    expect($resolved->preset)->toBe(RadioProductionPreset::Interlude);
    expect($resolved->loudnessI)->toBe(-18.0);
});

it('builds ffmpeg filters from preset values', function () {
    $preset = (new AudioProductionPresetResolver())->resolve(RadioShowType::Flash->value);

    $voice = (new VoiceChainFilter())->build(0, $preset, ['[voice]', '[voice_sc1]']);
    $sidechain = (new SidechainBedFilter())->build('[musicraw]', '[voice_sc1]', '[music]', $preset);

    expect($voice)->toContain('highpass=f=80');
    expect($voice)->toContain('asplit=2[voice][voice_sc1]');
    expect($sidechain)->toContain('ratio=12');
    expect($sidechain)->toContain('attack=12');
    expect($preset->loudnormFilter())->toBe('loudnorm=I=-14:TP=-1:LRA=7');
});
