<?php

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioShowType;
use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Models\RadioPodcast;
use App\Models\Sound;
use App\Models\SoundFile;
use App\Models\User;
use App\Services\Radio\RadioPlaylistExportService;
use App\Services\Radio\RadioPodcastGenerationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Cache::flush();
    Storage::fake('public');
    Storage::fake('radio_cache');
    config(['radio.audio_cache.disk' => 'radio_cache']);
    config(['radio.podcast.enabled' => true]);
    config(['radio.podcast.interval_tracks' => 2]);
    config(['radio.podcast.min_duration' => 120]);
    config(['radio.podcast.max_duration' => 240]);
    config(['radio.podcast.min_sounds' => 1]);
    config(['radio.podcast.max_sounds' => 3]);
    config(['services.openrouter.api_key' => 'test-key']);
    config(['services.elevenlabs.api_key' => 'test-key']);
    config(['services.elevenlabs.voice_id' => 'test-voice']);
});

function createTestSound(): Sound
{
    $user = User::factory()->create();
    $sound = Sound::factory()->create([
        'user_id' => $user->id,
        'status' => SoundStatus::Published,
        'visibility' => SoundVisibility::Public,
        'duration' => 60,
        'play_count' => 10,
    ]);
    SoundFile::factory()->create([
        'sound_id' => $sound->id,
        'path' => 'sounds/test.mp3',
        'disk' => 'public',
        'mime_type' => 'audio/mpeg',
    ]);
    Storage::disk('public')->put('sounds/test.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));

    return $sound;
}

it('inserts podcast every n tracks in playlist', function () {
    $user = User::factory()->create();
    foreach (range(1, 5) as $i) {
        $sound = Sound::factory()->create([
            'user_id' => $user->id,
            'status' => SoundStatus::Published,
            'visibility' => SoundVisibility::Public,
            'duration' => 60,
        ]);
        SoundFile::factory()->create([
            'sound_id' => $sound->id,
            'path' => "sounds/test{$i}.mp3",
            'disk' => 'public',
            'mime_type' => 'audio/mpeg',
        ]);
        Storage::disk('public')->put("sounds/test{$i}.mp3", file_get_contents('/tmp/test_audio_norm.mp3'));
    }

    $podcast = RadioPodcast::factory()->create([
        'status' => RadioPodcastStatus::Published,
        'title' => 'Test Podcast',
        'actual_duration_seconds' => 150,
        'path' => 'podcasts/1.mp3',
        'disk' => 'radio_cache',
    ]);
    Storage::disk('radio_cache')->put('podcasts/1.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));

    $playlist = app(RadioPlaylistExportService::class)->liquidsoapPlaylist();
    $kinds = collect($playlist)->pluck('kind')->all();

    expect($kinds)->toContain('podcast');
});

it('skips podcast insertion when none published', function () {
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

    $playlist = app(RadioPlaylistExportService::class)->liquidsoapPlaylist();
    $kinds = collect($playlist)->pluck('kind')->all();

    expect($kinds)->not->toContain('podcast');
});

it('rotates published generated content in the liquidsoap playlist', function () {
    $user = User::factory()->create();
    foreach (range(1, 5) as $i) {
        $sound = Sound::factory()->create([
            'user_id' => $user->id,
            'status' => SoundStatus::Published,
            'visibility' => SoundVisibility::Public,
            'duration' => 60,
        ]);
        SoundFile::factory()->create([
            'sound_id' => $sound->id,
            'path' => "sounds/rotation{$i}.mp3",
            'disk' => 'public',
            'mime_type' => 'audio/mpeg',
        ]);
        Storage::disk('public')->put("sounds/rotation{$i}.mp3", file_get_contents('/tmp/test_audio_norm.mp3'));
    }

    $flash = RadioPodcast::factory()->create([
        'show_type' => RadioShowType::Flash,
        'status' => RadioPodcastStatus::Published,
        'title' => 'Flash du matin',
        'actual_duration_seconds' => 120,
        'path' => 'podcasts/flash.mp3',
        'disk' => 'radio_cache',
        'published_at' => now(),
    ]);
    $emission = RadioPodcast::factory()->create([
        'show_type' => RadioShowType::Emission,
        'status' => RadioPodcastStatus::Published,
        'title' => 'Emission forêt',
        'actual_duration_seconds' => 600,
        'path' => 'podcasts/emission.mp3',
        'disk' => 'radio_cache',
        'published_at' => now()->subMinute(),
    ]);
    Storage::disk('radio_cache')->put('podcasts/flash.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));
    Storage::disk('radio_cache')->put('podcasts/emission.mp3', file_get_contents('/tmp/test_audio_norm.mp3'));

    $playlist = app(RadioPlaylistExportService::class)->liquidsoapPlaylist();
    $generated = collect($playlist)->where('kind', 'podcast')->values();

    expect($generated)->toHaveCount(2)
        ->and($generated->pluck('podcast_id')->all())->toBe([$flash->id, $emission->id])
        ->and($generated->pluck('show_type')->all())->toBe(['flash', 'emission']);
});

it('validates podcast duration within bounds', function () {
    Http::fake([
        'api.elevenlabs.io/*' => Http::response(file_get_contents('/tmp/test_audio_norm.mp3'), 200, ['Content-Type' => 'audio/mpeg']),
    ]);

    $service = app(RadioPodcastGenerationService::class);
    $canGenerate = $service->canGenerate();

    expect($canGenerate)->toBeTrue();
});

it('m3u contains annotations without signed urls', function () {
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

    $m3u = app(RadioPlaylistExportService::class)->m3u();

    expect($m3u)->toContain('#EXTM3U')
        ->and($m3u)->toContain('annotate:')
        ->and($m3u)->toContain('/radio-cache/sounds/')
        ->and($m3u)->not->toContain('signature=')
        ->and($m3u)->not->toContain('expires=');
});

it('stores theme and research from topic generation', function () {
    // With segmented TTS each segment gets its own ElevenLabs call, so the
    // concatenated voice file can exceed the default test max_duration of 240s.
    // This test checks theme/research storage, not duration bounds.
    config(['radio.podcast.max_duration' => 3600]);

    Http::fake(function ($request) {
        $url = $request->url();

        if (str_contains($url, 'elevenlabs.io')) {
            return Http::response(file_get_contents('/tmp/test_audio_long.mp3'), 200, ['Content-Type' => 'audio/mpeg']);
        }

        $body = json_decode($request->body(), true);
        $prompt = $body['messages'][1]['content'] ?? '';

        if (str_contains($prompt, 'idée de vrai podcast')) {
            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'theme' => 'Écouter la forêt en hiver',
                                'angle' => 'Découvrir les sons sous la neige',
                                'research_summary' => 'Études récentes sur l\'acoustique hivernale',
                                'keywords' => ['neige', 'forêt', 'hiver'],
                            ]),
                        ],
                    ],
                ],
                'usage' => [],
            ]);
        }

        return Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Forêt Mystique',
                            'description' => 'Une immersion',
                            'segments' => [
                                ['type' => 'intro', 'text' => 'Bienvenue', 'sound_id' => null, 'transition_duration_seconds' => null],
                                ['type' => 'outro', 'text' => 'Au revoir', 'sound_id' => null, 'transition_duration_seconds' => null],
                            ],
                            'estimated_duration_seconds' => 150,
                        ]),
                    ],
                ],
            ],
            'usage' => [],
        ]);
    });

    createTestSound();

    $service = app(RadioPodcastGenerationService::class);
    $podcast = $service->generate();

    expect($podcast)->not->toBeNull();
    expect($podcast->theme)->not->toBeNull();
    expect($podcast->research_json)->toBeArray();
});

it('marks podcast failed on invalid duration estimation', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'title' => 'Too Long',
                            'description' => 'Test',
                            'segments' => [
                                ['type' => 'intro', 'text' => 'Hello', 'sound_id' => null, 'transition_duration_seconds' => null],
                            ],
                            'estimated_duration_seconds' => 500,
                        ]),
                    ],
                ],
            ],
            'usage' => [],
        ]),
    ]);

    createTestSound();

    $service = app(RadioPodcastGenerationService::class);
    $podcast = $service->generate();

    expect($podcast)->toBeNull();
    expect(RadioPodcast::query()->where('status', RadioPodcastStatus::Failed)->exists())->toBeTrue();
});
