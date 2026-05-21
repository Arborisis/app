<?php

declare(strict_types=1);

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ArborisisPoint;
use App\Models\User;

it('returns nearby approved public points', function () {
    $user = User::factory()->create();

    // Point proche (Paris)
    $near = ArborisisPoint::factory()->create([
        'user_id' => $user->id,
        'title' => 'Bois de Vincennes',
        'approximate_latitude' => 48.855,
        'approximate_longitude' => 2.43,
        'moderation_status' => ModerationStatus::Approved,
        'visibility_status' => 'public',
        'category' => ArborisisCategory::Forest,
        'nature_sensitivity_level' => NatureSensitivityLevel::Normal,
    ]);

    // Point loin (Marseille)
    $far = ArborisisPoint::factory()->create([
        'user_id' => $user->id,
        'title' => 'Calanques',
        'approximate_latitude' => 43.28,
        'approximate_longitude' => 5.38,
        'moderation_status' => ModerationStatus::Approved,
        'visibility_status' => 'public',
        'category' => ArborisisCategory::Water,
        'nature_sensitivity_level' => NatureSensitivityLevel::Normal,
    ]);

    $response = $this->getJson('/api/arborisis-points/nearby?lat=48.8566&lng=2.3522&radius=10')
        ->assertOk();

    $features = $response->json('features');
    $titles = collect($features)->pluck('properties.title')->all();

    expect($titles)->toContain('Bois de Vincennes')
        ->not->toContain('Calanques');
});

it('validates latitude and longitude', function () {
    $this->getJson('/api/arborisis-points/nearby?lat=999&lng=2.3522')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lat']);
});
