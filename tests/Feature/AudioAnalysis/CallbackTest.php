<?php

use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundFile;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Config::set('services.analyzer.internal_api_token', 'test-secret-token');
    Storage::fake('r2');
});

it('processes a successful callback and updates analysis', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    SoundFile::factory()->create(['sound_id' => $sound->id, 'disk' => 'r2']);
    $analysis = SoundAnalysis::factory()->create(['sound_id' => $sound->id, 'status' => 'processing']);

    $payload = [
        'sound_id' => $sound->id,
        'status' => 'completed',
        'results' => [
            'original_r2_key' => "sounds/original/{$sound->id}/test.wav",
            'duration_seconds' => 123.45,
            'sample_rate' => 48000,
            'channels' => 2,
            'bitrate' => 1536000,
            'format' => 'wav',
            'loudness_lufs' => -18.4,
            'peak_db' => -1.2,
            'rms_db' => -24.7,
            'noise_floor_db' => -54.3,
            'spectral_centroid' => 3120.4,
            'spectral_rolloff' => 7600.2,
            'zero_crossing_rate' => 0.042,
            'waveform_r2_key' => "sounds/analysis/{$sound->id}/waveform.json",
            'spectrogram_r2_key' => "sounds/analysis/{$sound->id}/spectrogram.webp",
            'features_r2_key' => "sounds/analysis/{$sound->id}/features.json",
            'birdnet_r2_key' => "sounds/analysis/{$sound->id}/birdnet.json",
            'summary_r2_key' => "sounds/analysis/{$sound->id}/summary.json",
            'preview_r2_key' => "sounds/preview/{$sound->id}/preview.mp3",
            'quality_label' => 'good',
            'quality_json' => ['clipping_detected' => false, 'usable_for_analysis' => true],
            'birdnet_detections' => [
                [
                    'scientific_name' => 'Turdus merula',
                    'common_name' => 'Common Blackbird',
                    'confidence' => 0.87,
                    'start_time' => 12.0,
                    'end_time' => 15.0,
                ],
            ],
        ],
    ];

    $response = $this->postJson('/api/internal/audio-analysis/callback', $payload, [
        'Authorization' => 'Bearer test-secret-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('status', 'completed');

    $analysis->refresh();
    expect($analysis->status->value)->toBe('completed')
        ->and($analysis->duration_seconds)->toBe(123.45)
        ->and($analysis->birdnetDetections)->toHaveCount(1);
});

it('rejects callback with invalid token', function () {
    $response = $this->postJson('/api/internal/audio-analysis/callback', [], [
        'Authorization' => 'Bearer wrong-token',
    ]);

    $response->assertUnauthorized();
});

it('rejects callback with invalid payload', function () {
    $response = $this->postJson('/api/internal/audio-analysis/callback', [
        'sound_id' => 99999,
        'status' => 'completed',
        'results' => [],
    ], [
        'Authorization' => 'Bearer test-secret-token',
    ]);

    $response->assertUnprocessable();
});

it('is idempotent and ignores duplicate completed callback', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    SoundFile::factory()->create(['sound_id' => $sound->id]);
    SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'status' => 'completed',
        'duration_seconds' => 60.0,
    ]);

    $payload = [
        'sound_id' => $sound->id,
        'status' => 'completed',
        'results' => [
            'duration_seconds' => 99.99,
            'waveform_r2_key' => 'k',
            'spectrogram_r2_key' => 'k',
            'features_r2_key' => 'k',
            'summary_r2_key' => 'k',
        ],
    ];

    $response = $this->postJson('/api/internal/audio-analysis/callback', $payload, [
        'Authorization' => 'Bearer test-secret-token',
    ]);

    $response->assertOk();

    $sound->soundAnalysis->refresh();
    expect($sound->soundAnalysis->duration_seconds)->toBe(60.0);
});

it('processes a failed callback', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    SoundFile::factory()->create(['sound_id' => $sound->id]);
    SoundAnalysis::factory()->create(['sound_id' => $sound->id, 'status' => 'processing']);

    $payload = [
        'sound_id' => $sound->id,
        'status' => 'failed',
        'error_message' => 'FFmpeg timeout',
    ];

    $response = $this->postJson('/api/internal/audio-analysis/callback', $payload, [
        'Authorization' => 'Bearer test-secret-token',
    ]);

    $response->assertOk();

    $sound->soundAnalysis->refresh();
    expect($sound->soundAnalysis->status->value)->toBe('failed')
        ->and($sound->soundAnalysis->error_message)->toBe('FFmpeg timeout');
});
