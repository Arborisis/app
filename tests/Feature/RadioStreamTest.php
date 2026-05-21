<?php

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioShowType;
use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Enums\RadioScheduleRepeat;
use App\Models\RadioPodcast;
use App\Models\RadioSchedule;
use App\Models\Sound;
use App\Models\SoundFile;
use App\Models\User;
use App\Services\Radio\RadioStateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('public');
});

it('displays the radio page', function () {
    $response = $this->get('/radio');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Radio/Index')
        );
});

it('displays generated radio formats grouped for the public archive', function () {
    RadioPodcast::factory()->published()->create([
        'show_type' => RadioShowType::Podcast,
        'title' => 'Podcast foret',
        'published_at' => now()->subDays(2),
    ]);
    RadioPodcast::factory()->published()->create([
        'show_type' => RadioShowType::Flash,
        'title' => 'Flash biodiversite',
        'published_at' => now()->subDay(),
    ]);
    RadioPodcast::factory()->published()->create([
        'show_type' => RadioShowType::Emission,
        'title' => 'Emission canopee',
        'published_at' => now(),
    ]);
    RadioPodcast::factory()->create([
        'show_type' => RadioShowType::Flash,
        'status' => RadioPodcastStatus::Pending,
        'title' => 'Flash non publie',
        'path' => 'podcasts/pending.mp3',
    ]);

    $response = $this->get('/radio/programmes');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Radio/Shows')
            ->has('items', 3)
            ->where('counts.all', 3)
            ->where('counts.podcast', 1)
            ->where('counts.flash', 1)
            ->where('counts.emission', 1)
            ->where('items.0.show_type', 'emission')
            ->where('items.0.title', 'Emission canopee')
        );
});

it('returns audio/mpeg for stream endpoint', function () {
    $response = $this->get('/radio/stream');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'audio/mpeg');
});

it('returns a valid m3u playlist file', function () {
    $response = $this->get('/radio/stream.m3u');

    $response->assertStatus(200)
        ->assertHeader('Content-Type', 'audio/x-mpegurl')
        ->assertSee('#EXTM3U')
        ->assertSee('Arborisis Radio')
        ->assertSee('/radio/stream');
});

it('returns now-playing metadata as json', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Forest Morning',
        'duration' => 120,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/test.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/test.mp3', 'fake mp3 content');

    // Fixe l'epoch au moment présent pour que le schedule tombe sur le début d'un morceau
    Cache::put('radio:epoch', time());
    Cache::forget('radio:playlist');

    $service = app(\App\Services\Radio\RadioStreamService::class);
    $expected = $service->resolveCurrentSound();

    $response = $this->getJson('/api/radio/now-playing');

    $response->assertStatus(200)
        ->assertJsonPath('now_playing.title', $expected->title)
        ->assertJsonPath('now_playing.artist', $expected->user?->name ?? 'Arborisis')
        ->assertJsonPath('now_playing.sound_id', $expected->id)
        ->assertJsonPath('listener_count', 0)
        ->assertJsonStructure([
            'now_playing' => ['title', 'artist', 'cover', 'duration', 'started_at', 'sound_id', 'slug'],
            'next_up',
            'listener_count',
            'updated_at',
        ]);
});

it('accepts liquidsoap now-playing updates as the public radio state', function () {
    config(['radio.internal_token' => 'radio-test-token']);

    $user = User::factory()->create(['name' => 'Field Recordist']);
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Canopy Rain',
        'duration' => 95,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/canopy.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);

    $this->withToken('radio-test-token')
        ->postJson('/api/internal/radio/now-playing', [
            'sound_id' => $sound->id,
            'title' => 'Canopy Rain',
            'artist' => 'Field Recordist',
            'duration' => 95,
            'started_at' => '2026-05-12T10:00:00+00:00',
            'kind' => 'sound',
        ])
        ->assertOk()
        ->assertJsonPath('now_playing.title', 'Canopy Rain');

    $this->getJson('/api/radio/now-playing')
        ->assertOk()
        ->assertJsonPath('now_playing.title', 'Canopy Rain')
        ->assertJsonPath('now_playing.artist', 'Field Recordist')
        ->assertJsonPath('now_playing.sound_id', $sound->id);
});

it('protects the internal liquidsoap playlist endpoint', function () {
    config(['radio.internal_token' => 'radio-test-token']);

    $this->getJson('/api/internal/radio/playlist')->assertUnauthorized();
    $this->withToken('radio-test-token')->getJson('/api/internal/radio/playlist')->assertOk();
});

it('exports only playable public tracks for liquidsoap', function () {
    config(['radio.internal_token' => 'radio-test-token']);

    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Playable Forest',
        'duration' => 60,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/playable.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/playable.mp3', 'fake mp3');

    $private = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Private,
        'title' => 'Private Forest',
    ]);
    SoundFile::factory()->create([
        'sound_id' => $private->id,
        'path' => 'sounds/private.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/private.mp3', 'private mp3');

    $this->withToken('radio-test-token')
        ->getJson('/api/internal/radio/playlist')
        ->assertOk()
        ->assertJsonPath('tracks.0.title', 'Playable Forest')
        ->assertJsonMissing(['title' => 'Private Forest']);
});

it('uses the synchronized radio state for discord now playing', function () {
    config(['services.discord.internal_api_token' => 'discord-test-token']);

    app(RadioStateService::class)->update([
        'title' => 'Dawn Chorus',
        'artist' => 'Arborisis',
        'duration' => 120,
        'kind' => 'sound',
    ]);

    $this->withHeader('X-Internal-Token', 'discord-test-token')
        ->getJson('/api/internal/discord/radio/now-playing')
        ->assertOk()
        ->assertJsonPath('title', 'Dawn Chorus')
        ->assertJsonPath('creator', 'Arborisis');
});

it('keeps a playable original mp3 when a stale radio conversion is missing', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/original.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
        'radio_path' => 'sounds/missing-radio.mp3',
        'radio_mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/original.mp3', 'fake mp3 content');

    $service = app(\App\Services\Radio\RadioStreamService::class);

    expect($service->getPlayablePlaylist()->pluck('id'))->toContain($sound->id);
});

it('excludes sounds when neither radio conversion nor original mp3 is readable', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/original.wav',
        'disk' => 'public',
        'mime_type' => 'audio/wav',
        'radio_path' => 'sounds/missing-radio.mp3',
        'radio_mime_type' => 'audio/mpeg',
    ]);

    $service = app(\App\Services\Radio\RadioStreamService::class);

    expect($service->getPlayablePlaylist()->pluck('id'))->not->toContain($sound->id);
});

it('builds playlist from public sounds only', function () {
    $user = User::factory()->create();

    $published = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $published->id,
        'path' => 'sounds/published.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);

    $draft = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Draft,
        'visibility' => SoundVisibility::Public,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $draft->id,
        'path' => 'sounds/draft.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);

    $private = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Private,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $private->id,
        'path' => 'sounds/private.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);

    $service = app(\App\Services\Radio\RadioStreamService::class);
    $playlist = $service->getPlaylist();

    expect($playlist->pluck('id'))->toContain($published->id)
        ->and($playlist->pluck('id'))->not->toContain($draft->id)
        ->and($playlist->pluck('id'))->not->toContain($private->id);
});

it('tracks listener count on stream', function () {
    $service = app(\App\Services\Radio\RadioStreamService::class);

    expect($service->getListenerCount())->toBe(0);

    $service->incrementListeners();
    expect($service->getListenerCount())->toBe(1);

    $service->decrementListeners();
    expect($service->getListenerCount())->toBe(0);
});

it('can reset active radio listeners', function () {
    $service = app(\App\Services\Radio\RadioStreamService::class);

    $service->incrementListeners();
    expect($service->getListenerCount())->toBe(1);

    $service->resetListenerCount();
    expect($service->getListenerCount())->toBe(0);
});

it('does not decrement listeners below zero', function () {
    $service = app(\App\Services\Radio\RadioStreamService::class);

    $service->decrementListeners();
    expect($service->getListenerCount())->toBe(0);
});

it('uses an active radio schedule before the default playlist', function () {
    $user = User::factory()->create();

    $defaultSound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Default sound',
    ]);
    SoundFile::factory()->create([
        'sound_id' => $defaultSound->id,
        'path' => 'sounds/default.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/default.mp3', 'default mp3');

    $scheduledSound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Scheduled sound',
    ]);
    SoundFile::factory()->create([
        'sound_id' => $scheduledSound->id,
        'path' => 'sounds/scheduled.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/scheduled.mp3', 'scheduled mp3');

    $schedule = RadioSchedule::query()->create([
        'name' => 'Morning block',
        'starts_at' => now()->subHour(),
        'ends_at' => now()->addHour(),
        'repeat' => RadioScheduleRepeat::None,
        'priority' => 10,
        'is_active' => true,
    ]);
    $schedule->sounds()->attach($scheduledSound->id, ['position' => 1]);

    $service = app(\App\Services\Radio\RadioStreamService::class);

    expect($service->getPlaylist()->pluck('id')->all())->toBe([$scheduledSound->id]);
});

it('generates valid icy metadata', function () {
    $service = app(\App\Services\Radio\RadioStreamService::class);
    $meta = $service->generateIcyMetadata('Forest Rain', 'Jane Doe');

    expect($meta)->toBeString()
        ->toStartWith(chr(3))
        ->toContain('StreamTitle=')
        ->toContain('Forest Rain')
        ->toContain('Jane Doe');
});

it('excludes inertia middleware from stream route', function () {
    $response = $this->get('/radio/stream');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'audio/mpeg');
});

it('returns local cache urls in m3u playlist without signatures', function () {
    config(['radio.internal_token' => 'radio-test-token']);

    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'title' => 'Forest Morning',
        'duration' => 60,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/test.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/test.mp3', 'fake mp3 content');

    $m3u = app(\App\Services\Radio\RadioPlaylistExportService::class)->m3u();

    expect($m3u)->toContain('#EXTM3U')
        ->and($m3u)->toContain('annotate:')
        ->and($m3u)->toContain('/radio-cache/sounds/')
        ->and($m3u)->not->toContain('signature=')
        ->and($m3u)->not->toContain('expires=');
});

it('includes podcast kind in now playing when updated by liquidsoap', function () {
    config(['radio.internal_token' => 'radio-test-token']);

    app(\App\Services\Radio\RadioStateService::class)->update([
        'title' => 'Nature Capsule',
        'artist' => 'Arborisis Radio',
        'duration' => 180,
        'kind' => 'podcast',
        'podcast_id' => 1,
    ]);

    $response = $this->getJson('/api/radio/now-playing');

    $response->assertOk()
        ->assertJsonPath('now_playing.kind', 'podcast')
        ->assertJsonPath('now_playing.podcast_id', 1);
});
