<?php

declare(strict_types=1);

use App\Models\BirdnetDetection;
use App\Models\EnvironmentalObservation;
use App\Models\ListeningPoint;
use App\Models\ScientificMetric;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundLocation;

beforeEach(function () {
    BirdnetDetection::query()->delete();
    EnvironmentalObservation::query()->delete();
    ScientificMetric::query()->delete();
    SoundLocation::query()->delete();
    SoundAnalysis::query()->delete();
    Sound::query()->forceDelete();
    ListeningPoint::query()->forceDelete();
});

it('exposes public species statistics with scientific response metadata', function () {
    $sound = Sound::factory()->create();
    $analysis = SoundAnalysis::factory()->create(['sound_id' => $sound->id]);

    BirdnetDetection::query()->create([
        'sound_analysis_id' => $analysis->id,
        'sound_id' => $sound->id,
        'scientific_name' => 'Parus major',
        'common_name' => 'Mesange charbonniere',
        'confidence' => 0.93,
        'start_time' => 1.0,
        'end_time' => 2.0,
        'source' => 'birdnet',
    ]);

    $this->getJson(route('api.scientific-stats.species', ['min_confidence' => 0.8]))
        ->assertOk()
        ->assertJsonPath('data.0.scientific_name', 'Parus major')
        ->assertJsonPath('data.0.sounds_count', 1)
        ->assertJsonPath('meta.schema_version', 1)
        ->assertJsonPath('meta.privacy.scope', 'public_published_sounds_only');
});

it('validates scientific API query parameters', function () {
    $this->getJson(route('api.scientific-stats.species', ['min_confidence' => 1.5]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['min_confidence']);
});

it('exposes environmental scientific statistics', function () {
    $sound = Sound::factory()->create();

    EnvironmentalObservation::query()->create([
        'sound_id' => $sound->id,
        'season' => 'spring',
        'time_of_day' => 'dawn',
        'temperature_c' => 9.5,
        'humidity_percent' => 90,
        'wind_speed_kmh' => 6.5,
        'weather_condition' => 'fog',
        'source' => 'open-meteo',
    ]);

    $this->getJson(route('api.scientific-stats.environmental'))
        ->assertOk()
        ->assertJsonPath('data.coverage.percentage', 100)
        ->assertJsonPath('data.sources.0.source', 'open-meteo')
        ->assertJsonPath('data.weather_conditions.0.condition', 'fog')
        ->assertJsonPath('meta.privacy.coordinates', 'public_obfuscated_coordinates_only');
});

it('includes weather context in model statistics', function () {
    $sound = Sound::factory()->create();

    SoundLocation::query()->create([
        'sound_id' => $sound->id,
        'public_latitude' => 48.85,
        'public_longitude' => 2.35,
        'location_name' => 'Paris',
    ]);

    EnvironmentalObservation::query()->create([
        'sound_id' => $sound->id,
        'season' => 'spring',
        'time_of_day' => 'dawn',
        'temperature_c' => 11.5,
        'humidity_percent' => 88,
        'wind_speed_kmh' => 4.0,
        'weather_condition' => 'mist',
        'source' => 'open-meteo',
    ]);

    ScientificMetric::query()->create([
        'measurable_type' => Sound::class,
        'measurable_id' => $sound->id,
        'metric_type' => 'acoustic_activity_score',
        'granularity' => 'overall',
        'value' => 42.5,
        'computed_at' => now(),
        'sample_size' => 1,
        'status' => 'complete',
    ]);

    ScientificMetric::query()->create([
        'measurable_type' => Sound::class,
        'measurable_id' => $sound->id,
        'metric_type' => 'biodiversity_score',
        'granularity' => 'overall',
        'value' => 64.0,
        'computed_at' => now(),
        'sample_size' => 1,
        'status' => 'complete',
    ]);

    $this->getJson(route('api.scientific-stats.model-stats'))
        ->assertOk()
        ->assertJsonPath('data.acoustic_activity_score.mean', 42.5)
        ->assertJsonPath('data.biodiversity_score.mean', 64)
        ->assertJsonPath('data.weather_context.coverage.percentage', 100)
        ->assertJsonPath('data.weather_context.averages.temperature_c', 11.5)
        ->assertJsonPath('data.weather_context.weather_conditions.0.condition', 'mist')
        ->assertJsonPath('data.weather_context.activity_by_weather_condition.0.mean_acoustic_activity_score', 42.5)
        ->assertJsonPath('data.weather_context.biodiversity_by_weather_condition.0.mean_value', 64)
        ->assertJsonPath('data.weather_context.individual_sounds.0.sound_id', $sound->id)
        ->assertJsonPath('data.weather_context.individual_sounds.0.weather.weather_condition', 'mist');
});

it('includes public listening point weather in model statistics', function () {
    $point = ListeningPoint::factory()->approved()->create([
        'public_latitude' => 48.85,
        'public_longitude' => 2.35,
        'approved_at' => now(),
    ]);

    EnvironmentalObservation::query()->create([
        'sound_id' => null,
        'listening_point_id' => $point->id,
        'season' => 'spring',
        'time_of_day' => 'morning',
        'temperature_c' => 13.5,
        'humidity_percent' => 75,
        'wind_speed_kmh' => 7.0,
        'weather_condition' => 'cloudy',
        'source' => 'open-meteo-point',
    ]);

    $this->getJson(route('api.scientific-stats.model-stats'))
        ->assertOk()
        ->assertJsonPath('data.weather_context.listening_points.coverage.percentage', 100)
        ->assertJsonPath('data.weather_context.listening_points.averages.temperature_c', 13.5)
        ->assertJsonPath('data.weather_context.listening_points.weather_conditions.0.condition', 'cloudy');
});

it('exposes a paginated research dataset with schema and no exact coordinates', function () {
    $sound = Sound::factory()->create([
        'title' => 'Aube en foret',
        'equipment' => 'Zoom F3',
        'microphone_position' => 'canopy edge',
    ]);

    SoundLocation::query()->create([
        'sound_id' => $sound->id,
        'exact_latitude' => 48.856613,
        'exact_longitude' => 2.352222,
        'public_latitude' => 48.850000,
        'public_longitude' => 2.350000,
        'location_name' => 'Paris',
    ]);

    $analysis = SoundAnalysis::factory()->create([
        'sound_id' => $sound->id,
        'sample_rate' => 48000,
        'loudness_lufs' => -22.4,
        'spectral_centroid' => 2300.0,
        'zero_crossing_rate' => 0.12,
        'processed_at' => now(),
    ]);

    BirdnetDetection::query()->create([
        'sound_analysis_id' => $analysis->id,
        'sound_id' => $sound->id,
        'scientific_name' => 'Parus major',
        'common_name' => 'Mesange charbonniere',
        'confidence' => 0.93,
        'start_time' => 1.0,
        'end_time' => 2.0,
        'source' => 'birdnet',
    ]);

    ScientificMetric::query()->create([
        'measurable_type' => Sound::class,
        'measurable_id' => $sound->id,
        'metric_type' => 'biodiversity_score',
        'granularity' => 'overall',
        'value' => 67.5,
        'computed_at' => now(),
        'sample_size' => 1,
        'status' => 'complete',
    ]);

    $response = $this->getJson(route('api.scientific-stats.dataset', ['limit' => 1, 'offset' => 0]))
        ->assertOk()
        ->assertJsonPath('data.dataset.schema_version', 2)
        ->assertJsonPath('data.dataset.returned', 1)
        ->assertJsonPath('data.rows.0.title', 'Aube en foret')
        ->assertJsonPath('data.rows.0.public_latitude', 48.85)
        ->assertJsonPath('data.rows.0.top_species.0.scientific_name', 'Parus major')
        ->assertJsonPath('data.rows.0.biodiversity_score', 67.5)
        ->assertJsonPath('meta.privacy.coordinates', 'public_obfuscated_coordinates_only');

    expect($response->json('data.rows.0'))->not->toHaveKeys(['exact_latitude', 'exact_longitude', 'user_id']);
    expect($response->json('data.schema.0.name'))->toBe('sound_id');
});
