<?php

declare(strict_types=1);

namespace Tests\Feature\Agent;

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AgentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_point_via_agent(): void
    {
        $response = $this->postJson('/api/agent/actions/create-point', [
            'title' => 'Test Point',
            'latitude' => 45.9,
            'longitude' => 6.8,
            'category' => ArborisisCategory::Forest->value,
        ]);

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_point_via_agent(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->postJson('/api/agent/actions/create-point', [
            'title' => 'Cascade de la Folie',
            'latitude' => 45.9,
            'longitude' => 6.8,
            'category' => ArborisisCategory::Water->value,
            'nature_sensitivity_level' => 'normal',
            'tags' => ['cascade', 'montagne'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('created_resource.type', 'arborisis_point')
            ->assertJsonPath('created_resource.title', 'Cascade de la Folie')
            ->assertJsonPath('created_resource.moderation_status', 'pending');

        $this->assertDatabaseHas('arborisis_points', [
            'title' => 'Cascade de la Folie',
            'user_id' => $user->id,
            'moderation_status' => ModerationStatus::Pending->value,
        ]);
    }

    public function test_authenticated_user_can_create_sound_walk_via_agent(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        config()->set('services.nominatim.enabled', false);
        config()->set('services.osrm.enabled', false);

        $response = $this->actingAs($user)->postJson('/api/agent/actions/create-itinerary', [
            'title' => 'Balade en forêt de Fontainebleau',
            'description' => 'Une balade sonore à travers les différents écosystèmes de la forêt.',
            'waypoints' => [
                [
                    'title' => 'Entrée principale',
                    'lat' => 48.4,
                    'lng' => 2.7,
                    'order' => 0,
                    'recording_tips' => 'Arriver tôt le matin pour les chants d\'oiseaux.',
                ],
                [
                    'title' => 'Ruisseau des Marais',
                    'lat' => 48.41,
                    'lng' => 2.71,
                    'order' => 1,
                    'recording_tips' => 'Utiliser un pare-vent pour l\'eau.',
                ],
                [
                    'title' => 'Clairière aux cerfs',
                    'lat' => 48.42,
                    'lng' => 2.72,
                    'order' => 2,
                ],
            ],
            'estimated_duration_minutes' => 120,
            'difficulty_level' => 2,
            'tags' => ['forêt', 'eau', 'faune'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('created_resource.type', 'sound_walk')
            ->assertJsonPath('created_resource.title', 'Balade en forêt de Fontainebleau')
            ->assertJsonPath('created_resource.moderation_status', 'pending')
            ->assertJsonPath('created_resource.waypoints_count', 3);

        $this->assertDatabaseHas('sound_walks', [
            'title' => 'Balade en forêt de Fontainebleau',
            'user_id' => $user->id,
            'moderation_status' => ModerationStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('sound_walk_points', [
            'title' => 'Entrée principale',
            'order' => 0,
        ]);
    }

    public function test_agent_created_sound_walk_uses_geocoder_and_walking_router(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Http::fake([
            'nominatim.openstreetmap.org/search*Ferme*' => Http::response([[
                'lat' => '50.900194',
                'lon' => '4.384775',
                'display_name' => 'Ferme Nos Pilifs, Bruxelles',
            ]]),
            'nominatim.openstreetmap.org/search*Josaphat*' => Http::response([[
                'lat' => '50.860035',
                'lon' => '4.382243',
                'display_name' => 'Parc Josaphat, Schaerbeek',
            ]]),
            'routing.openstreetmap.de/routed-foot/route/v1/foot/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 5123.4,
                    'duration' => 4210.6,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [4.384775, 50.900194],
                            [4.381000, 50.880000],
                            [4.382243, 50.860035],
                        ],
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($user)->postJson('/api/agent/actions/create-itinerary', [
            'title' => 'SoundWalk Bruxelles Nord-Est · Schaerbeek / Laeken',
            'waypoints' => [
                [
                    'title' => 'Ferme Nos Pilifs',
                    'place_query' => 'Ferme Nos Pilifs, Bruxelles, Belgique',
                    'order' => 0,
                ],
                [
                    'title' => 'Parc Josaphat',
                    'place_query' => 'Parc Josaphat, Schaerbeek, Bruxelles, Belgique',
                    'order' => 1,
                ],
            ],
            'estimated_duration_minutes' => 120,
        ]);

        $response->assertCreated();

        $soundWalk = \App\Models\SoundWalk::with('points')->firstOrFail();

        expect($soundWalk->route_geometry['source'])->toBe('osrm')
            ->and($soundWalk->route_geometry['coordinates'])->toHaveCount(3)
            ->and($soundWalk->route_geometry['distance_meters'])->toBe(5123.4)
            ->and((float) $soundWalk->points->last()->latitude)->toBe(50.860035)
            ->and((float) $soundWalk->points->last()->longitude)->toBe(4.382243);

        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'routing.openstreetmap.de/routed-foot/route/v1/foot/')
            && str_contains((string) $request->url(), '4.384775,50.900194;4.382243,50.860035'));
    }

    public function test_sound_walk_route_preview_resolves_osm_places_before_creation(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/search*Parc*' => Http::response([[
                'lat' => '50.8916404',
                'lon' => '4.3511190',
                'display_name' => 'Parc de Laeken, Bruxelles',
            ]]),
            'nominatim.openstreetmap.org/search*Dynastie*' => Http::response([[
                'lat' => '50.8904774',
                'lon' => '4.3533286',
                'display_name' => 'Monument à la Dynastie, Laeken',
            ]]),
            'routing.openstreetmap.de/routed-foot/route/v1/foot/*' => Http::response([
                'code' => 'Ok',
                'routes' => [[
                    'distance' => 987.6,
                    'duration' => 654.3,
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [
                            [4.3511190, 50.8916404],
                            [4.3533286, 50.8904774],
                        ],
                    ],
                ]],
            ]),
        ]);

        $preview = app(\App\Services\Gamification\SoundWalkService::class)->previewItinerary([
            'title' => 'Boucle réelle de Laeken',
            'waypoints' => [
                ['title' => 'Parc de Laeken', 'place_query' => 'Parc de Laeken, Bruxelles, Belgique', 'order' => 0],
                ['title' => 'Monument de la Dynastie', 'place_query' => 'Monument de la Dynastie, Laeken, Bruxelles, Belgique', 'order' => 1],
            ],
        ]);

        expect($preview['waypoints'][0]['lat'])->toBe(50.8916404)
            ->and($preview['waypoints'][0]['geocoded_source'])->toBe('nominatim')
            ->and($preview['route']['source'])->toBe('osrm')
            ->and($preview['route']['coordinates_count'])->toBe(2)
            ->and($preview['route']['distance_meters'])->toBe(987.6);
    }

    public function test_agent_created_sound_walk_rejects_unresolved_place_query_instead_of_random_coordinates(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Http::fake([
            'nominatim.openstreetmap.org/search*' => Http::response([]),
        ]);

        $response = $this->actingAs($user)->postJson('/api/agent/actions/create-itinerary', [
            'title' => 'Balade impossible',
            'waypoints' => [
                [
                    'title' => 'Lieu inventé',
                    'place_query' => 'Lieu inventé qui ne doit pas exister, Bruxelles, Belgique',
                    'lat' => 50.0,
                    'lng' => 4.0,
                    'order' => 0,
                ],
                [
                    'title' => 'Autre lieu inventé',
                    'place_query' => 'Autre lieu inventé qui ne doit pas exister, Bruxelles, Belgique',
                    'lat' => 50.1,
                    'lng' => 4.1,
                    'order' => 1,
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['waypoints']);

        $this->assertDatabaseCount('sound_walks', 0);
        $this->assertDatabaseCount('sound_walk_points', 0);
    }

    public function test_create_point_validates_coordinates(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->postJson('/api/agent/actions/create-point', [
            'title' => 'Bad Point',
            'latitude' => 999,
            'longitude' => 6.8,
            'category' => ArborisisCategory::Forest->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['latitude']);
    }

    public function test_create_itinerary_requires_at_least_two_waypoints(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->postJson('/api/agent/actions/create-itinerary', [
            'title' => 'Too Short',
            'waypoints' => [
                ['lat' => 48.4, 'lng' => 2.7, 'order' => 0],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['waypoints']);
    }

    public function test_rate_limiting_blocks_excessive_agent_actions(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        // The rate limit is 10 per hour; make 11 requests quickly
        for ($i = 0; $i < 11; $i++) {
            $response = $this->actingAs($user)->postJson('/api/agent/actions/create-point', [
                'title' => "Point {$i}",
                'latitude' => 45.9,
                'longitude' => 6.8,
                'category' => ArborisisCategory::Forest->value,
            ]);
        }

        $response->assertStatus(429);
    }
}
