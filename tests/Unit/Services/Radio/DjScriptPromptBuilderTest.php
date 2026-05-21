<?php

declare(strict_types=1);

use App\Enums\RadioDaypart;
use App\Models\RadioHostPersonality;
use App\Models\Sound;
use App\Models\User;
use App\Services\Radio\Prompts\DjScriptPromptBuilder;

beforeEach(function () {
    $this->builder = new DjScriptPromptBuilder();
});

function makePersonality(array $overrides = []): RadioHostPersonality
{
    return new RadioHostPersonality(array_merge([
        'slug' => 'solene_poete',
        'display_name' => 'Solène — la poète',
        'voice_provider' => 'elevenlabs',
        'voice_id' => 'voice_solene',
        'voice_settings' => ['stability' => 0.42, 'similarity_boost' => 0.78, 'style' => 0.35],
        'prose_brief' => 'Tu incarnes Solène, voix posée et poétique, registre sensoriel.',
        'forbidden_phrases' => ['Vous écoutez', 'Aujourd\'hui sur Arborisis'],
        'preferred_lexicon' => ['frémissement', 'lisière', 'souffle'],
        'dayparts' => [RadioDaypart::Dawn->value],
        'show_types' => ['dj'],
        'is_active' => true,
        'priority' => 50,
    ], $overrides));
}

function makeSoundStub(): Sound
{
    $user = (new User())->forceFill(['name' => 'Aurélie Renard']);
    $sound = new Sound();
    $sound->forceFill([
        'id' => 42,
        'title' => 'Aube en hêtraie',
        'duration' => 185,
    ]);
    $sound->setRelation('user', $user);

    return $sound;
}

it('embeds the prose brief, forbidden phrases and lexicon into the system prompt', function () {
    $personality = makePersonality();
    $sound = makeSoundStub();

    $prompt = $this->builder->build(
        sound: $sound,
        personality: $personality,
        daypart: RadioDaypart::Dawn,
    );

    expect($prompt['system'])->toContain('Tu incarnes Solène');
    expect($prompt['system'])->toContain('frémissement');
    expect($prompt['system'])->toContain('Vous écoutez');
    expect($prompt['system'])->toContain('Tu réponds UNIQUEMENT en JSON strict');
});

it('feeds the recent openings as forbidden start phrases in the user prompt', function () {
    $personality = makePersonality();
    $sound = makeSoundStub();

    $prompt = $this->builder->build(
        sound: $sound,
        personality: $personality,
        daypart: RadioDaypart::Dawn,
        recentOpenings: [
            'Dans la lumière encore voilée',
            'Le souffle de la hêtraie',
        ],
    );

    expect($prompt['user'])->toContain('Dans la lumière encore voilée');
    expect($prompt['user'])->toContain('Le souffle de la hêtraie');
    expect($prompt['user'])->toContain('Évite de commencer comme tes intros précédentes');
});

it('mentions the previous track when continuity context is provided', function () {
    $personality = makePersonality();
    $sound = makeSoundStub();

    $prompt = $this->builder->build(
        sound: $sound,
        personality: $personality,
        daypart: RadioDaypart::Dawn,
        previousSound: ['title' => 'Grives litornes au crépuscule', 'creator' => 'Marc Verdier'],
    );

    expect($prompt['user'])->toContain('Grives litornes au crépuscule');
    expect($prompt['user'])->toContain('Marc Verdier');
});

it('renders the species and acoustic profile lines when payload is provided', function () {
    $personality = makePersonality();
    $sound = makeSoundStub();

    $prompt = $this->builder->build(
        sound: $sound,
        personality: $personality,
        daypart: RadioDaypart::Dawn,
        soundPayload: [
            'location' => 'Hêtraie des Vosges',
            'analysis' => [
                'main_detected_species' => [
                    ['name' => 'Merle noir', 'confidence' => 0.82],
                    ['name' => 'Rouge-gorge', 'confidence' => 0.65],
                ],
                'acoustic_profile' => [
                    'spectral_centroid_hz' => 2340,
                    'event_density' => 1.4,
                ],
            ],
        ],
    );

    expect($prompt['user'])->toContain('Merle noir');
    expect($prompt['user'])->toContain('Hêtraie des Vosges');
    expect($prompt['user'])->toContain('centroïde spectral');
    expect($prompt['user'])->toContain('densité d\'événements');
});

it('produces distinct system prompts for two different personalities', function () {
    $solene = makePersonality();
    $marc = makePersonality([
        'slug' => 'marc_naturaliste',
        'display_name' => 'Marc — le naturaliste',
        'prose_brief' => 'Tu incarnes Marc, naturaliste, voix chaleureuse et documentaire.',
        'preferred_lexicon' => ['biotope', 'hêtraie', 'chant territorial'],
        'forbidden_phrases' => ['Mes chers auditeurs'],
        'dayparts' => [RadioDaypart::Morning->value],
        'show_types' => ['dj'],
    ]);
    $sound = makeSoundStub();

    $promptSolene = $this->builder->build(sound: $sound, personality: $solene, daypart: RadioDaypart::Dawn);
    $promptMarc = $this->builder->build(sound: $sound, personality: $marc, daypart: RadioDaypart::Morning);

    expect($promptSolene['system'])->not->toEqual($promptMarc['system']);
    expect($promptSolene['system'])->toContain('frémissement');
    expect($promptMarc['system'])->toContain('biotope');
});

it('asks the JSON response to expose an opening fingerprint', function () {
    $prompt = $this->builder->build(
        sound: makeSoundStub(),
        personality: makePersonality(),
        daypart: RadioDaypart::Dawn,
    );

    expect($prompt['user'])->toContain('"opening"');
    expect($prompt['user'])->toContain('"mentions"');
});

it('signals when no continuity is needed', function () {
    $prompt = $this->builder->build(
        sound: makeSoundStub(),
        personality: makePersonality(),
        daypart: RadioDaypart::Dawn,
        previousSound: null,
    );

    expect($prompt['user'])->toContain('Il n\'y a pas de transition');
});

it('normalizes opening text for fingerprinting via the public helper', function () {
    $selector = new App\Services\Radio\HostPersonalitySelector();

    expect($selector->openingFromText('Dans la lumière, encore voilée du matin… on entend.'))
        ->toBe('dans la lumière encore voilée du matin on');

    expect($selector->openingFromText('Une, deux, trois, quatre, cinq, six, sept, huit, neuf.'))
        ->toBe('une deux trois quatre cinq six sept huit');
});
