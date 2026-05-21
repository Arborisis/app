<?php

declare(strict_types=1);

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ArborisisPoint;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email_verified_at' => now(),
    ]);
});

describe('création de point', function () {
    it('permet à un utilisateur vérifié de créer un point', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/arborisis-points', [
                'title' => 'Point de test',
                'description' => 'Description test',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'category' => ArborisisCategory::Forest->value,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('point.title', 'Point de test')
            ->assertJsonPath('point.moderation_status', 'En attente');

        $this->assertDatabaseHas('arborisis_points', [
            'title' => 'Point de test',
            'moderation_status' => ModerationStatus::Pending->value,
            'user_id' => $this->user->id,
        ]);
    });

    it('masque les coordonnées exactes dans la réponse', function () {
        $point = ArborisisPoint::factory()->create([
            'latitude' => 48.8566,
            'longitude' => 2.3522,
            'approximate_latitude' => 48.86,
            'approximate_longitude' => 2.35,
            'moderation_status' => ModerationStatus::Approved,
        ]);

        $response = $this->getJson("/api/arborisis-points/{$point->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('latitude', 48.86)
            ->assertJsonPath('longitude', 2.35)
            ->assertJsonMissing(['latitude' => 48.8566]);
    });

    it('floute automatiquement les coordonnées pour les lieux sensibles', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/arborisis-points', [
                'title' => 'Nid d\'aigle',
                'latitude' => 48.8566,
                'longitude' => 2.3522,
                'category' => ArborisisCategory::Birds->value,
                'nature_sensitivity_level' => NatureSensitivityLevel::SensitiveSpecies->value,
            ]);

        $response->assertStatus(201);

        $point = ArborisisPoint::latest()->first();

        expect((float) $point->latitude)->toBe(48.8566)
            ->and((float) $point->longitude)->toBe(2.3522)
            ->and((float) $point->approximate_latitude)->toBe(48.86)
            ->and((float) $point->approximate_longitude)->toBe(2.35);
    });

    it('conserve la position publique exacte pour les lieux non sensibles', function () {
        $response = $this->actingAs($this->user)
            ->postJson('/api/arborisis-points', [
                'title' => 'Clairière ouverte',
                'latitude' => 48.85661234,
                'longitude' => 2.35222345,
                'category' => ArborisisCategory::Forest->value,
                'nature_sensitivity_level' => NatureSensitivityLevel::Normal->value,
            ]);

        $response->assertStatus(201);

        $point = ArborisisPoint::latest()->first();

        expect((float) $point->approximate_latitude)->toBe(48.85661234)
            ->and((float) $point->approximate_longitude)->toBe(2.35222345);
    });
});

describe('modération', function () {
    it('seuls les points approuvés apparaissent dans la liste publique', function () {
        ArborisisPoint::factory()->create([
            'title' => 'Approuvé',
            'moderation_status' => ModerationStatus::Approved,
        ]);
        ArborisisPoint::factory()->create([
            'title' => 'En attente',
            'moderation_status' => ModerationStatus::Pending,
        ]);

        $response = $this->getJson('/api/arborisis-points');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.title', 'Approuvé');
    });
});

describe('signalements', function () {
    it('permet de signaler un point', function () {
        $point = ArborisisPoint::factory()->create([
            'moderation_status' => ModerationStatus::Approved,
        ]);
        $reporter = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($reporter)
            ->postJson("/api/arborisis-points/{$point->slug}/report", [
                'reason' => 'inaccurate_location',
                'description' => 'La position est incorrecte',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('point_reports', [
            'arborisis_point_id' => $point->id,
            'user_id' => $reporter->id,
            'reason' => 'inaccurate_location',
        ]);
    });
});
