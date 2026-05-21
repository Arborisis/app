<?php

use App\Models\Sound;
use App\Models\SoundFile;
use App\Models\User;
use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Services\Radio\RadioAudioCacheService;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('radio_cache');
    config(['radio.audio_cache.disk' => 'radio_cache']);
    config(['app.url' => 'http://localhost']);
});

it('warms sound to local cache and normalizes', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'duration' => 60,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/test.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/test.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));

    $cache = app(RadioAudioCacheService::class);
    $path = $cache->warmSound($sound);

    expect($cache->exists('sounds', $sound->id))->toBeTrue()
        ->and($path)->toBeString()
        ->and(Storage::disk('radio_cache')->exists("sounds/{$sound->id}.mp3"))->toBeTrue();
});

it('returns stable url for cached asset', function () {
    $cache = app(RadioAudioCacheService::class);
    $url = $cache->urlFor('sounds', 42);
    $base = rtrim(config('app.url'), '/');

    expect($url)->toBe("{$base}/radio/cache/sounds/42")
        ->and($url)->not->toContain('?');
});

it('serves cached file via endpoint', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'duration' => 60,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/test.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/test.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));

    $cache = app(RadioAudioCacheService::class);
    $cache->warmSound($sound);

    $response = $this->get("/radio/cache/sounds/{$sound->id}");

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'audio/mpeg');
});

it('returns 404 for missing cache file', function () {
    $this->get('/radio/cache/sounds/99999')
        ->assertStatus(404);
});

it('rebuilds cache for all playable assets', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/test.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/test.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));

    $cache = app(RadioAudioCacheService::class);
    $cache->rebuild();

    expect($cache->exists('sounds', $sound->id))->toBeTrue();
});

it('cleans orphan cache files', function () {
    $disk = Storage::disk('radio_cache');
    $disk->put('sounds/99999.mp3', 'fake');

    $cache = app(RadioAudioCacheService::class);
    $cache->cleanup();

    expect($disk->exists('sounds/99999.mp3'))->toBeFalse();
});
