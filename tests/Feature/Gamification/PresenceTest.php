<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

describe('presence', function () {
    it('updates user presence', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/presence/update', [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'visibility_mode' => 'approximate',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('presence.visibility_mode', 'approximate');

        $this->assertDatabaseHas('user_presences', [
            'user_id' => $this->user->id,
            'visibility_mode' => 'approximate',
        ]);
    });

    it('removes user presence', function () {
        $this->actingAs($this->user)
            ->postJson('/api/presence/update', [
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'visibility_mode' => 'approximate',
            ]);

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/presence');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('user_presences', [
            'user_id' => $this->user->id,
        ]);
    });
});
