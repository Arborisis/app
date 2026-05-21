<?php

declare(strict_types=1);

use App\Models\Sound;
use App\Models\SpeciesFact;
use App\Services\Radio\RadioHostContextService;
use App\Services\Radio\StorytellingEnricher;

it('builds grounded ecology blurbs from species facts', function () {
    SpeciesFact::query()->create([
        'latin_name' => 'Turdus merula',
        'common_name_fr' => 'Merle noir',
        'group' => 'oiseau',
        'fact_fr' => 'Le mâle entonne ses sifflées riches dès la fin de l\'hiver.',
        'habitat' => 'jardins, lisières, parcs urbains',
        'seasonality' => 'chante de février à juillet',
        'source' => 'INPN',
    ]);

    $sound = Sound::factory()->create([
        'title' => 'Matin sous les hêtres',
        'recorded_at' => now()->month(4)->day(12),
    ]);
    $previous = Sound::factory()->create(['title' => 'Pluie sur la canopée']);

    $context = Mockery::mock(RadioHostContextService::class);
    $context->shouldReceive('soundPayload')
        ->once()
        ->with(Mockery::type(Sound::class))
        ->andReturn([
            'location' => 'Vosges',
            'analysis' => [
                'main_detected_species' => [
                    [
                        'latin_name' => 'Turdus merula',
                        'common_name_fr' => 'Merle noir',
                        'confidence' => 0.91,
                    ],
                ],
                'acoustic_profile' => [
                    'event_density' => 1.8,
                ],
            ],
        ]);

    $result = (new StorytellingEnricher($context))->enrich($sound, $previous);

    expect($result['species_blurb'])->toContain('Merle noir');
    expect($result['species_blurb'])->toContain('sifflées riches');
    expect($result['location_blurb'])->toContain('Vosges');
    expect($result['season_blurb'])->toContain('printemps');
    expect($result['transition'])->toContain('Pluie sur la canopée');
    expect($result['fun_fact'])->toContain('jardins');
});

it('injects storytelling hints in the DJ prompt when continuity is enabled', function () {
    config(['radio.host.continuity_enabled' => true]);

    $sound = Sound::factory()->create(['title' => 'Aube en hêtraie']);

    $personality = \App\Models\RadioHostPersonality::query()->create([
        'slug' => 'solene_poete',
        'display_name' => 'Solène',
        'voice_provider' => 'elevenlabs',
        'voice_id' => 'voice',
        'voice_settings' => [],
        'prose_brief' => 'Tu es une voix douce.',
        'forbidden_phrases' => [],
        'preferred_lexicon' => [],
        'dayparts' => ['dawn'],
        'show_types' => ['dj'],
        'is_active' => true,
        'priority' => 10,
    ]);

    $context = Mockery::mock(RadioHostContextService::class);
    $context->shouldReceive('soundPayload')->andReturn(null);

    $storytelling = Mockery::mock(StorytellingEnricher::class);
    $storytelling->shouldReceive('enrich')
        ->once()
        ->andReturn([
            'species_blurb' => 'Merle noir : chant de lisière au printemps.',
            'location_blurb' => null,
            'season_blurb' => 'Saison d\'écoute : printemps.',
            'transition' => 'Ouverture autonome.',
            'fun_fact' => null,
            'species' => null,
        ]);

    $generator = new \App\Services\Radio\DjScriptGenerator(
        new \App\Services\Radio\Prompts\DjScriptPromptBuilder(),
        new \App\Services\Radio\HostPersonalitySelector(),
        Mockery::mock(\App\Services\AI\OpenRouterService::class),
        $context,
        $storytelling,
    );

    $prepared = $generator->prepare(
        sound: $sound,
        personality: $personality,
        daypart: \App\Enums\RadioDaypart::Dawn,
    );

    expect($prepared['prompts']['user'])->toContain('ENRICHISSEMENT ÉDITORIAL');
    expect($prepared['prompts']['user'])->toContain('Merle noir');
});

afterEach(function () {
    Mockery::close();
});
