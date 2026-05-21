<?php

use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;

it('allows owner to retry analysis', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'status' => 'failed',
        'attempts' => 1,
    ]);

    $response = $this->actingAs($user)
        ->postJson("/api/sounds/{$sound->id}/analysis/retry");

    $response->assertOk()
        ->assertJsonPath('status', 'pending')
        ->assertJsonPath('attempts', 2);
});

it('allows admin to retry analysis', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);
    $sound = Sound::factory()->create(['user_id' => $owner->id]);
    SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'status' => 'failed',
        'attempts' => 0,
    ]);

    $response = $this->actingAs($admin)
        ->postJson("/api/sounds/{$sound->id}/analysis/retry");

    $response->assertOk()
        ->assertJsonPath('status', 'pending');
});

it('prevents non-owner from retrying', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $owner->id]);
    SoundAnalysis::factory()->create(['sound_id' => $sound->id, 'status' => 'failed']);

    $response = $this->actingAs($other)
        ->postJson("/api/sounds/{$sound->id}/analysis/retry");

    $response->assertForbidden();
});

it('throttles retry endpoint', function () {
    $user = User::factory()->create();
    $sound = Sound::factory()->create(['user_id' => $user->id]);
    SoundAnalysis::factory()->create(['sound_id' => $sound->id, 'status' => 'failed']);

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($user)
            ->postJson("/api/sounds/{$sound->id}/analysis/retry")
            ->assertOk();
    }

    $this->actingAs($user)
        ->postJson("/api/sounds/{$sound->id}/analysis/retry")
        ->assertStatus(429);
});
