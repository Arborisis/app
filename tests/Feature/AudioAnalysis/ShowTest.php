<?php

use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;

it('returns analysis status for authenticated user', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    \App\Models\SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'status' => 'completed',
        'duration_seconds' => 120.0,
        'sample_rate' => 48000,
        'format' => 'wav',
        'spectrogram_r2_key' => 'sounds/analysis/1/spectrogram.webp',
        'waveform_r2_key' => 'sounds/analysis/1/waveform.json',
        'features_r2_key' => 'sounds/analysis/1/features.json',
        'summary_r2_key' => 'sounds/analysis/1/summary.json',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/sounds/{$sound->id}/analysis");

    $response->assertOk()
        ->assertJsonPath('analysis.status', 'completed')
        ->assertJsonPath('analysis.duration_seconds', 120)
        ->assertJsonPath('analysis.sample_rate', 48000);
});

it('returns null analysis when none exists', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)
        ->getJson("/api/sounds/{$sound->id}/analysis");

    $response->assertOk()
        ->assertJsonPath('analysis', null);
});

it('requires authentication for private sound analysis', function () {
    $sound = Sound::factory()->create(['visibility' => 'private', 'status' => 'published']);

    $response = $this->getJson("/api/sounds/{$sound->id}/analysis");

    $response->assertStatus(403);
});
