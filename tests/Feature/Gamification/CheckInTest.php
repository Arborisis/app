<?php

declare(strict_types=1);

use App\Enums\ModerationStatus;
use App\Models\ArborisisPoint;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
        'geo_consent_given_at' => now(),
    ]);
});

describe('check-in', function () {
    it('allows check-in when within range', function () {
        $point = ArborisisPoint::factory()->create([
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'moderation_status' => ModerationStatus::Approved,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/arborisis-points/{$point->slug}/visit", [
                'latitude' => 48.8567,
                'longitude' => 2.3523,
                'accuracy' => 10,
                'consent_given' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('visit.distance', fn ($d) => $d < 100);

        $this->assertDatabaseHas('arborisis_visits', [
            'user_id' => $this->user->id,
            'arborisis_point_id' => $point->id,
            'status' => 'valid',
        ]);
    });

    it('rejects check-in when too far', function () {
        $point = ArborisisPoint::factory()->create([
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'moderation_status' => ModerationStatus::Approved,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/arborisis-points/{$point->slug}/visit", [
                'latitude' => 49.0,
                'longitude' => 3.0,
                'accuracy' => 10,
                'consent_given' => true,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('reason', 'too_far');
    });

    it('requires consent', function () {
        $point = ArborisisPoint::factory()->create([
            'moderation_status' => ModerationStatus::Approved,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/arborisis-points/{$point->slug}/visit", [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'consent_given' => false,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('requires_consent', true);
    });
});
