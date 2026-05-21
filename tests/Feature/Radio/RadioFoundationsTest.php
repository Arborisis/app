<?php

declare(strict_types=1);

use App\Enums\RadioDaypart;
use App\Enums\RadioHostPersonality;
use App\Enums\RadioListenerSessionStatus;
use App\Enums\RadioProductionPreset;
use App\Enums\RadioShowType;
use App\Models\RadioChannel;
use App\Models\RadioGenerationJob;
use App\Models\RadioHostPersonality as RadioHostPersonalityModel;
use App\Models\RadioListenerSession;
use App\Models\RadioSetting;
use Database\Seeders\RadioChannelSeeder;
use Database\Seeders\RadioHostPersonalitySeeder;

it('exposes the daypart enum with predictable hour ranges', function () {
    expect(RadioDaypart::fromHour(6))->toBe(RadioDaypart::Dawn);
    expect(RadioDaypart::fromHour(10))->toBe(RadioDaypart::Morning);
    expect(RadioDaypart::fromHour(15))->toBe(RadioDaypart::Afternoon);
    expect(RadioDaypart::fromHour(19))->toBe(RadioDaypart::Evening);
    expect(RadioDaypart::fromHour(23))->toBe(RadioDaypart::Night);
    expect(RadioDaypart::fromHour(2))->toBe(RadioDaypart::Night);
});

it('maps production presets to show types', function () {
    expect(RadioProductionPreset::forShowType(RadioShowType::Podcast))
        ->toBe(RadioProductionPreset::PodcastIntimate);
    expect(RadioProductionPreset::forShowType(RadioShowType::Flash))
        ->toBe(RadioProductionPreset::FlashPunchy);
    expect(RadioProductionPreset::forShowType(RadioShowType::Emission))
        ->toBe(RadioProductionPreset::EmissionCinematic);
});

it('lists selectable host personalities without the auto sentinel', function () {
    $selectable = RadioHostPersonality::selectable();

    expect($selectable)->toContain(RadioHostPersonality::SolenePoete);
    expect($selectable)->toContain(RadioHostPersonality::MarcNaturaliste);
    expect($selectable)->toContain(RadioHostPersonality::LeaJournaliste);
    expect($selectable)->toContain(RadioHostPersonality::ArtoDocumentariste);
    expect($selectable)->not->toContain(RadioHostPersonality::Auto);
});

it('seeds the four host personalities with daypart and show type metadata', function () {
    $this->seed(RadioHostPersonalitySeeder::class);

    $solene = RadioHostPersonalityModel::query()->where('slug', 'solene_poete')->first();
    expect($solene)->not->toBeNull();
    expect($solene->supportsDaypart(RadioDaypart::Dawn))->toBeTrue();
    expect($solene->supportsDaypart(RadioDaypart::Morning))->toBeFalse();
    expect($solene->supportsRadioShowType(RadioShowType::Emission))->toBeTrue();
    expect($solene->voiceSettingsArray())->toHaveKeys([
        'stability', 'similarity_boost', 'style', 'use_speaker_boost',
    ]);

    $marc = RadioHostPersonalityModel::query()->where('slug', 'marc_naturaliste')->first();
    expect($marc->supportsRadioShowType(RadioShowType::Podcast))->toBeTrue();
    expect($marc->supportsDaypart(RadioDaypart::Morning))->toBeTrue();
});

it('seeds the main radio channel as the default broadcast surface', function () {
    $this->seed(RadioChannelSeeder::class);

    $main = RadioChannel::main();
    expect($main)->not->toBeNull();
    expect($main->slug)->toBe('main');
    expect($main->is_active)->toBeTrue();
    expect($main->production_preset)->toBe(RadioProductionPreset::EmissionCinematic);
});

it('exposes feature flags through the radio setting helper', function () {
    $features = RadioSetting::query()->firstOrFail()->features();

    expect($features)->toHaveKeys([
        'host_personalities',
        'host_continuity',
        'production_presets',
        'player_visualizer',
        'player_realtime',
        'monitoring',
        'multichannel',
        'discord_embeds',
    ]);

    foreach ($features as $value) {
        expect(is_bool($value))->toBeTrue();
    }
});

it('persists and queries listener sessions with active scope', function () {
    $session = RadioListenerSession::query()->create([
        'session_token' => 'test-token-1',
        'status' => RadioListenerSessionStatus::Active,
        'started_at' => now(),
        'last_heartbeat_at' => now(),
    ]);

    expect(RadioListenerSession::active()->count())->toBeGreaterThanOrEqual(1);

    $session->update([
        'status' => RadioListenerSessionStatus::Closed,
        'ended_at' => now(),
    ]);

    expect($session->refresh()->status)->toBe(RadioListenerSessionStatus::Closed);
});

it('persists radio generation jobs with idempotency keys', function () {
    $job = RadioGenerationJob::query()->create([
        'kind' => RadioGenerationJob::KIND_PODCAST,
        'idempotency_key' => 'test-podcast-key-1',
        'status' => RadioGenerationJob::STATUS_PENDING,
        'payload' => ['theme' => 'forest'],
    ]);

    expect($job->payload)->toBe(['theme' => 'forest']);
    expect($job->status)->toBe(RadioGenerationJob::STATUS_PENDING);
});

it('reads the production preset configuration map', function () {
    $presets = config('radio.presets');

    expect($presets)->toHaveKeys([
        'podcast_intimate',
        'flash_punchy',
        'emission_cinematic',
        'dj_brief',
        'interlude',
    ]);

    foreach ($presets as $preset) {
        expect($preset)->toHaveKeys([
            'loudness_i',
            'loudness_tp',
            'loudness_lra',
            'sidechain_ratio',
            'voice_highpass_hz',
            'music_volume',
            'field_bed_volume',
            'ident_volume',
        ]);
    }
});
