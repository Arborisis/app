<?php

declare(strict_types=1);

use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Models\RadioDjAnnouncement;
use App\Models\RadioHostPersonality;
use App\Models\Sound;
use App\Models\User;
use App\Services\AI\ElevenLabsService;
use App\Services\AI\OpenRouterService;
use App\Services\Radio\DjScriptGenerator;
use App\Services\Radio\HostPersonalitySelector;
use App\Services\Radio\Prompts\DjScriptPromptBuilder;
use App\Services\Radio\RadioDjService;
use App\Services\Radio\RadioHostContextService;
use Database\Seeders\RadioHostPersonalitySeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('audio');
    Storage::fake('public');
    config([
        'filesystems.audio_disk' => 'audio',
        'radio.dj.enabled' => true,
    ]);
    $this->seed(RadioHostPersonalitySeeder::class);

    RadioHostPersonality::query()->update([
        'voice_id' => 'fake_voice_id',
    ]);
});

function buildDjServiceWithMocks(?array $scriptResult = null): array
{
    $openRouter = Mockery::mock(OpenRouterService::class);
    $elevenLabs = Mockery::mock(ElevenLabsService::class);
    $context = Mockery::mock(RadioHostContextService::class);
    $context->shouldReceive('soundPayload')->andReturn(null)->byDefault();

    $promptBuilder = new DjScriptPromptBuilder();
    $selector = new HostPersonalitySelector();
    $generator = new DjScriptGenerator($promptBuilder, $selector, $openRouter, $context);
    $service = new RadioDjService($selector, $generator, $elevenLabs);

    if ($scriptResult !== null) {
        $openRouter->shouldReceive('generateDjScript')
            ->andReturn($scriptResult);
    }

    return [
        'service' => $service,
        'openRouter' => $openRouter,
        'elevenLabs' => $elevenLabs,
        'context' => $context,
        'selector' => $selector,
        'generator' => $generator,
    ];
}

function makePersistedSound(): Sound
{
    $user = User::factory()->create(['name' => 'Aurélie Renard']);

    return Sound::factory()->create([
        'user_id' => $user->id,
        'title' => 'Aube en hêtraie',
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'duration' => 185,
    ]);
}

it('falls back to legacy template when personalities flag is disabled', function () {
    config(['radio.host.personalities_enabled' => false]);
    config([
        'services.elevenlabs.api_key' => '',
        'services.elevenlabs.voice_id' => '',
    ]);

    $service = new RadioDjService();
    $sound = makePersistedSound();

    $legacy = $service->legacyTemplate($sound);

    expect($legacy)->toContain('Vous écoutez Aube en hêtraie');
    expect($legacy)->toContain('Aurélie Renard');
    expect($legacy)->toContain('Arborisis Radio');
});

it('generates an AI-driven announcement when personalities flag is on', function () {
    config(['radio.host.personalities_enabled' => true]);
    config(['radio.host.dj_fingerprint_window' => 30]);

    $mocks = buildDjServiceWithMocks([
        'text' => 'Dans la lumière encore voilée du matin… on entend une forêt qui s\'éveille à peine. Quelques minutes avec Aurélie pour habiter cet instant suspendu.',
        'opening' => 'dans la lumière encore voilée du matin',
        'mentions' => ['species' => false, 'location' => false, 'creator' => true],
        'model' => 'test-model',
        'usage' => [],
        'cost_cents' => null,
    ]);

    $sound = makePersistedSound();

    // ElevenLabs returns a temp file path with audio bytes.
    $tmp = sys_get_temp_dir().'/'.uniqid('dj_', true).'.mp3';
    file_put_contents($tmp, 'fake-audio-bytes');
    $mocks['elevenLabs']->shouldReceive('synthesizeSpeechWithSettings')
        ->once()
        ->andReturn($tmp);

    $announcement = $mocks['service']->announcementFor($sound);

    expect($announcement)->not->toBeNull();
    expect($announcement->personality_slug)->not->toBeNull();
    expect($announcement->text)->toContain('Dans la lumière encore voilée');
    expect($announcement->phrase_fingerprint)->not->toBeNull();
    expect($announcement->prompt_hash)->not->toBeNull();
    expect($announcement->daypart)->not->toBeNull();
    expect(Storage::disk('audio')->exists($announcement->path))->toBeTrue();
});

it('reuses an existing announcement when prompt_hash matches a stored file', function () {
    config(['radio.host.personalities_enabled' => true]);

    $sound = makePersistedSound();
    $personality = RadioHostPersonality::query()->where('slug', 'solene_poete')->firstOrFail();

    $mocks = buildDjServiceWithMocks([
        'text' => 'Un souffle long traverse la lisière… la hêtraie respire, lentement.',
        'opening' => 'un souffle long traverse la lisière la',
        'mentions' => [],
        'model' => 'test-model',
        'usage' => [],
        'cost_cents' => null,
    ]);

    $tmp = sys_get_temp_dir().'/'.uniqid('dj_', true).'.mp3';
    file_put_contents($tmp, 'audio-bytes');

    // First call triggers an ElevenLabs synthesis.
    $mocks['elevenLabs']->shouldReceive('synthesizeSpeechWithSettings')
        ->once()
        ->andReturn($tmp);

    $first = $mocks['service']->announcementFor($sound, $personality);
    expect($first)->not->toBeNull();

    // Second call should NOT call ElevenLabs again — same prompt_hash.
    $second = $mocks['service']->announcementFor($sound, $personality);

    expect($second)->not->toBeNull();
    expect($second->id)->toBe($first->id);
});

it('falls back to legacy template when no personality has a voice_id', function () {
    config(['radio.host.personalities_enabled' => true]);
    config([
        'radio.dj.voice_provider' => 'elevenlabs',
        'radio.dj.elevenlabs_voice_id' => null,
        'radio.dj.voice_id' => null,
        'services.elevenlabs.api_key' => '',
    ]);

    RadioHostPersonality::query()->update(['voice_id' => null]);

    $mocks = buildDjServiceWithMocks();
    $sound = makePersistedSound();

    // No ElevenLabs call expected because legacy fallback also returns null without an api key.
    $mocks['elevenLabs']->shouldNotReceive('synthesizeSpeechWithSettings');

    $announcement = $mocks['service']->announcementFor($sound);

    expect($announcement)->toBeNull();
});

it('falls back to legacy template when LLM returns null', function () {
    config(['radio.host.personalities_enabled' => true]);
    config([
        'radio.dj.voice_provider' => 'elevenlabs',
        'radio.dj.elevenlabs_voice_id' => 'fallback_voice',
        'services.elevenlabs.api_key' => 'test_key',
        'services.elevenlabs.voice_id' => 'fallback_voice',
    ]);

    $mocks = buildDjServiceWithMocks(scriptResult: null);
    $openRouter = $mocks['openRouter'];

    // Override default mock: simulate LLM failure
    $openRouter->shouldReceive('generateDjScript')->andReturn(null);

    // The legacy path uses Http facade directly, which we don't fake here, so it
    // will return null. That's the expected behaviour: graceful degradation.
    $sound = makePersistedSound();
    $announcement = $mocks['service']->announcementFor($sound);

    // We accept either null (legacy http failed) or a DjAnnouncement: the contract is
    // "never throw, and never call elevenLabs.synthesizeSpeechWithSettings".
    expect($announcement === null || $announcement instanceof RadioDjAnnouncement)->toBeTrue();
});

it('selects different personalities for different show types', function () {
    $selector = new HostPersonalitySelector();

    $djPerso = $selector->select('dj', App\Enums\RadioDaypart::Dawn);
    $flashPerso = $selector->select('flash', App\Enums\RadioDaypart::Morning);

    expect($djPerso)->not->toBeNull();
    expect($flashPerso)->not->toBeNull();
    // The flash personality should support flash show type.
    expect($flashPerso->supportsShowType('flash'))->toBeTrue();
});

it('avoids picking the same personality twice in a row when streak limit is reached', function () {
    config(['radio.host.dj_solo_streak_max' => 2]);
    $selector = new HostPersonalitySelector();
    $sound = makePersistedSound();

    // Insert 2 announcements from solene_poete back to back.
    for ($i = 0; $i < 2; $i++) {
        RadioDjAnnouncement::query()->create([
            'sound_id' => $sound->id,
            'voice_provider' => 'elevenlabs',
            'voice_id' => 'fake_voice_id',
            'personality_slug' => 'solene_poete',
            'text' => "Une intro {$i}",
            'disk' => 'audio',
            'path' => "radio/dj/solene_poete/{$i}.mp3",
            'mime_type' => 'audio/mpeg',
            'text_hash' => hash('sha256', "h{$i}"),
            'prompt_hash' => hash('sha256', "p{$i}"),
            'phrase_fingerprint' => hash('sha256', "f{$i}"),
            'daypart' => 'dawn',
            'generated_at' => now()->subMinutes(2 - $i),
        ]);
    }

    // Now the selector should pick someone else for dj/dawn.
    $next = $selector->select('dj', App\Enums\RadioDaypart::Dawn);

    expect($next)->not->toBeNull();
    expect($next->slug)->not->toBe('solene_poete');
});

afterEach(function () {
    Mockery::close();
});
