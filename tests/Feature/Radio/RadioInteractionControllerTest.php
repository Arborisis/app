<?php

declare(strict_types=1);

use App\Models\RadioReaction;
use App\Models\Sound;

it('likes the current radio sound idempotently by session token', function () {
    $sound = Sound::factory()->create(['like_count' => 0]);

    $payload = [
        'session_token' => 'session-123',
        'sound_id' => $sound->id,
    ];

    $this->postJson('/api/radio/interactions/like', $payload)
        ->assertOk()
        ->assertJsonPath('liked', true)
        ->assertJsonPath('like_count', 1);

    $this->postJson('/api/radio/interactions/like', $payload)
        ->assertOk()
        ->assertJsonPath('like_count', 1);

    expect($sound->fresh()->like_count)->toBe(1);
    expect(RadioReaction::query()->where('reaction_type', 'like')->count())->toBe(1);
});

it('stores lightweight reactions and returns a summary', function () {
    $sound = Sound::factory()->create();

    $this->postJson('/api/radio/interactions/react', [
        'session_token' => 'session-456',
        'sound_id' => $sound->id,
        'reaction_type' => 'leaf',
    ])
        ->assertOk()
        ->assertJsonPath('summary.leaf', 1);
});

it('returns a radio share payload', function () {
    $sound = Sound::factory()->create(['title' => 'Ruisseau sous les pins']);

    $this->postJson('/api/radio/interactions/share', [
        'sound_id' => $sound->id,
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Ruisseau sous les pins')
        ->assertJsonStructure(['url', 'text', 'title']);
});
