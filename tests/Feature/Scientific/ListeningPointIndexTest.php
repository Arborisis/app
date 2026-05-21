<?php

declare(strict_types=1);

use App\Models\BirdnetDetection;
use App\Models\ListeningPoint;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes a filtered public listening point catalogue', function () {
    $forest = ListeningPoint::factory()->approved()->create([
        'title' => 'Canopée de Soignes',
        'description' => 'Lisière ancienne suivie au printemps',
        'habitat_type' => 'forest',
        'recordings_count' => 9,
        'species_detected_count' => 0,
        'admin_level_1' => 'Bruxelles',
        'dominant_tags' => ['aube', 'pics'],
        'last_recorded_at' => now()->subDay(),
    ]);
    $forestSound = Sound::factory()->create(['listening_point_id' => $forest->id]);
    $forestAnalysis = SoundAnalysis::factory()->create(['sound_id' => $forestSound->id]);
    BirdnetDetection::factory()->create([
        'sound_analysis_id' => $forestAnalysis->id,
        'sound_id' => $forestSound->id,
        'scientific_name' => 'Turdus merula',
        'common_name' => 'Merle noir',
    ]);
    BirdnetDetection::factory()->create([
        'sound_analysis_id' => $forestAnalysis->id,
        'sound_id' => $forestSound->id,
        'scientific_name' => 'Parus major',
        'common_name' => 'Mésange charbonnière',
    ]);

    $wetland = ListeningPoint::factory()->approved()->create([
        'title' => 'Roselière du matin',
        'description' => 'Zone humide ouverte',
        'habitat_type' => 'wetland',
        'recordings_count' => 3,
        'species_detected_count' => 0,
        'admin_level_1' => 'Namur',
        'last_recorded_at' => now()->subDays(3),
    ]);
    $wetlandSound = Sound::factory()->create(['listening_point_id' => $wetland->id]);
    $wetlandAnalysis = SoundAnalysis::factory()->create(['sound_id' => $wetlandSound->id]);
    BirdnetDetection::factory()->create([
        'sound_analysis_id' => $wetlandAnalysis->id,
        'sound_id' => $wetlandSound->id,
        'scientific_name' => 'Anas platyrhynchos',
        'common_name' => 'Canard colvert',
    ]);

    $response = $this->get('/listening-points?habitat=forest&q=soignes&sort=active');

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('ListeningPoints/Index')
            ->where('filters.habitat', 'forest')
            ->where('filters.q', 'soignes')
            ->where('filters.sort', 'active')
            ->where('catalog.total_points', 2)
            ->where('catalog.total_recordings', 12)
            ->where('catalog.total_species_signals', 3)
            ->has('catalog.habitats', 2)
            ->has('points.data', 1)
            ->where('points.data.0.id', $forest->id)
            ->where('points.data.0.title', 'Canopée de Soignes')
            ->where('points.data.0.recordings_count', 9)
            ->where('points.data.0.species_detected_count', 2)
            ->missing('points.data.0.exact_latitude')
            ->missing('points.data.0.exact_longitude')
        );
});

it('prefills sound publication from an existing listening point', function () {
    $user = User::factory()->create();
    $point = ListeningPoint::factory()->approved()->create([
        'title' => 'Ferme Nos Pilifs',
        'public_latitude' => 50.90471109,
        'public_longitude' => 4.39102630,
        'public_accuracy_meters' => 100,
    ]);

    $response = $this->actingAs($user)->get("/sounds/create?listening_point_id={$point->id}");

    $response
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sounds/Create')
            ->where('selectedListeningPoint.id', $point->id)
            ->where('selectedListeningPoint.slug', $point->slug)
            ->where('selectedListeningPoint.title', 'Ferme Nos Pilifs')
            ->where('selectedListeningPoint.public_latitude', 50.90471109)
            ->where('selectedListeningPoint.public_longitude', 4.39102630)
            ->missing('selectedListeningPoint.exact_latitude')
            ->missing('selectedListeningPoint.exact_longitude')
        );
});
