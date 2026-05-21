<?php

declare(strict_types=1);

use App\Models\BirdnetDetection;
use App\Models\EnvironmentalObservation;
use App\Models\ScientificMetric;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundLocation;
use App\Services\Scientific\OpenMeteoEnvironmentalDataService;
use App\Services\Scientific\ScientificMetricService;
use App\Services\Scientific\ScientificStatsService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    BirdnetDetection::query()->delete();
    EnvironmentalObservation::query()->delete();
    ScientificMetric::query()->delete();
    SoundLocation::query()->delete();
    SoundAnalysis::query()->delete();
    Sound::query()->forceDelete();
});

describe('ScientificStatsService', function () {
    it('uses normalized audio metric columns when features_json is missing', function () {
        $soundA = Sound::factory()->create();
        $soundB = Sound::factory()->create();

        SoundAnalysis::factory()->create([
            'sound_id' => $soundA->id,
            'features_json' => null,
            'rms_db' => -41.88,
            'spectral_centroid' => 2151.32,
            'spectral_rolloff' => 4476.91,
            'zero_crossing_rate' => 0.1385,
        ]);

        SoundAnalysis::factory()->create([
            'sound_id' => $soundB->id,
            'features_json' => null,
            'rms_db' => -52.25,
            'spectral_centroid' => 2548.49,
            'spectral_rolloff' => 4753.42,
            'zero_crossing_rate' => 0.1887,
        ]);

        $service = new ScientificStatsService();

        $averages = $service->getAudioFeatureAverages();
        $distributions = $service->getAudioFeatureDistribution();

        expect($averages['rms']['count'])->toBe(2);
        expect($averages['rms']['mean'])->toBe(-47.065);
        expect($averages['spectral_centroid']['count'])->toBe(2);
        expect($averages['spectral_rolloff']['count'])->toBe(2);
        expect($averages['zcr']['count'])->toBe(2);
        expect($distributions['rms'])->toHaveCount(20);
    });

    it('reads nested feature stats from features_json', function () {
        $sound = Sound::factory()->create();

        SoundAnalysis::factory()->create([
            'sound_id' => $sound->id,
            'features_json' => [
                'temporal' => [
                    'rms' => ['stats' => ['mean' => 0.5]],
                    'zcr' => ['stats' => ['mean' => 0.12]],
                ],
                'spectral' => [
                    'centroid' => ['stats' => ['mean' => 2300.0]],
                    'rolloff' => ['stats' => ['mean' => 4700.0]],
                    'bandwidth' => ['stats' => ['mean' => 900.0]],
                ],
            ],
            'rms_db' => null,
            'spectral_centroid' => null,
            'spectral_rolloff' => null,
            'zero_crossing_rate' => null,
        ]);

        $averages = (new ScientificStatsService())->getAudioFeatureAverages();

        expect($averages['rms']['mean'])->toBe(0.5);
        expect($averages['zcr']['mean'])->toBe(0.12);
        expect($averages['spectral_centroid']['mean'])->toBe(2300.0);
        expect($averages['spectral_rolloff']['mean'])->toBe(4700.0);
        expect($averages['spectral_bandwidth']['mean'])->toBe(900.0);
    });

    it('summarizes public species detections with confidence filtering', function () {
        $publicSound = Sound::factory()->create();
        $privateSound = Sound::factory()->private()->create();

        $publicAnalysis = SoundAnalysis::factory()->create(['sound_id' => $publicSound->id]);
        $privateAnalysis = SoundAnalysis::factory()->create(['sound_id' => $privateSound->id]);

        BirdnetDetection::query()->create([
            'sound_analysis_id' => $publicAnalysis->id,
            'sound_id' => $publicSound->id,
            'scientific_name' => 'Turdus merula',
            'common_name' => 'Merle noir',
            'confidence' => 0.91,
            'start_time' => 1.0,
            'end_time' => 3.5,
            'source' => 'birdnet',
        ]);

        BirdnetDetection::query()->create([
            'sound_analysis_id' => $publicAnalysis->id,
            'sound_id' => $publicSound->id,
            'scientific_name' => 'Turdus merula',
            'common_name' => 'Merle noir',
            'confidence' => 0.42,
            'start_time' => 6.0,
            'end_time' => 7.0,
            'source' => 'birdnet',
        ]);

        BirdnetDetection::query()->create([
            'sound_analysis_id' => $privateAnalysis->id,
            'sound_id' => $privateSound->id,
            'scientific_name' => 'Erithacus rubecula',
            'common_name' => 'Rougegorge familier',
            'confidence' => 0.99,
            'start_time' => 1.0,
            'end_time' => 2.0,
            'source' => 'birdnet',
        ]);

        $species = (new ScientificStatsService())->getSpeciesDistribution(filters: ['min_confidence' => 0.8]);

        expect($species)->toHaveCount(1);
        expect($species[0]['scientific_name'])->toBe('Turdus merula');
        expect($species[0]['detections_count'])->toBe(1);
        expect($species[0]['sounds_count'])->toBe(1);
    });

    it('computes quality and metadata completeness for public scientific data', function () {
        $sound = Sound::factory()->create([
            'equipment' => 'Zoom F3 + parabolique',
        ]);

        SoundLocation::query()->create([
            'sound_id' => $sound->id,
            'public_latitude' => 48.85000000,
            'public_longitude' => 2.35000000,
            'location_name' => 'Paris',
        ]);

        $analysis = SoundAnalysis::factory()->create([
            'sound_id' => $sound->id,
            'quality_label' => 'good',
            'duration_seconds' => 120.0,
            'sample_rate' => 48000,
            'loudness_lufs' => -23.5,
            'noise_floor_db' => -61.0,
            'spectral_centroid' => 2400.0,
            'zero_crossing_rate' => 0.12,
            'processed_at' => now(),
        ]);

        BirdnetDetection::query()->create([
            'sound_analysis_id' => $analysis->id,
            'sound_id' => $sound->id,
            'scientific_name' => 'Parus major',
            'common_name' => 'Mesange charbonniere',
            'confidence' => 0.88,
            'start_time' => 2.0,
            'end_time' => 4.0,
            'source' => 'birdnet',
        ]);

        EnvironmentalObservation::query()->create([
            'sound_id' => $sound->id,
            'season' => 'spring',
            'time_of_day' => 'morning',
            'temperature_c' => 14.2,
            'humidity_percent' => 72,
            'wind_speed_kmh' => 5.5,
            'weather_condition' => 'cloudy',
            'source' => 'open-meteo',
        ]);

        $service = new ScientificStatsService();

        $quality = $service->getQualityOverview();
        $completeness = $service->getDatasetCompleteness();

        expect($quality['analyses_count'])->toBe(1);
        expect($quality['quality_labels'][0]['label'])->toBe('good');
        expect($quality['metric_coverage']['sample_rate']['percentage'])->toBe(100.0);
        expect($completeness['sounds_count'])->toBe(1);
        expect($completeness['scientific_readiness_score'])->toBe(100.0);
    });

    it('computes biodiversity from unique high confidence species and activity from louder signals', function () {
        $sound = Sound::factory()->create();
        $analysis = SoundAnalysis::factory()->create([
            'sound_id' => $sound->id,
            'loudness_lufs' => -18.0,
            'rms_db' => -20.0,
            'zero_crossing_rate' => 0.22,
            'spectral_centroid' => 3500.0,
            'acoustic_event_count' => 60,
            'acoustic_diversity_index' => 2.1,
        ]);

        foreach ([
            ['Turdus merula', 0.92],
            ['Turdus merula', 0.88],
            ['Parus major', 0.86],
            ['Erithacus rubecula', 0.41],
        ] as [$species, $confidence]) {
            BirdnetDetection::query()->create([
                'sound_analysis_id' => $analysis->id,
                'sound_id' => $sound->id,
                'scientific_name' => $species,
                'common_name' => $species,
                'confidence' => $confidence,
                'start_time' => 1.0,
                'end_time' => 2.0,
                'source' => 'birdnet',
            ]);
        }

        $service = new ScientificMetricService();
        $biodiversity = $service->computeBiodiversityScore($sound->fresh(['soundAnalysis.birdnetDetections', 'tags']));
        $activity = $service->computeAcousticActivityScore($sound->fresh(['soundAnalysis']));

        expect($biodiversity?->components['species_count'])->toBe(2);
        expect($biodiversity?->components['validated_detection_count'])->toBe(3);
        expect($biodiversity?->components['confidence_threshold'])->toBe(0.70);
        expect((float) $activity?->value)->toBeGreaterThan(70.0);
    });

    it('enriches environmental observations from Open-Meteo using public coordinates', function () {
        Http::fake([
            'archive-api.open-meteo.com/*' => Http::response([
                'hourly' => [
                    'time' => ['2026-05-16T05:00', '2026-05-16T06:00'],
                    'temperature_2m' => [11.2, 12.4],
                    'relative_humidity_2m' => [84, 81],
                    'precipitation' => [0.0, 0.4],
                    'rain' => [0.0, 0.4],
                    'snowfall' => [0.0, 0.0],
                    'weather_code' => [3, 61],
                    'wind_speed_10m' => [8.1, 10.2],
                    'wind_direction_10m' => [92, 181],
                ],
            ]),
        ]);

        $sound = Sound::factory()->create([
            'recorded_at' => '2026-05-16 06:10:00',
        ]);

        SoundLocation::query()->create([
            'sound_id' => $sound->id,
            'exact_latitude' => 48.856613,
            'exact_longitude' => 2.352222,
            'public_latitude' => 48.850000,
            'public_longitude' => 2.350000,
            'location_name' => 'Paris',
        ]);

        $observation = (new OpenMeteoEnvironmentalDataService())->enrichSound($sound);

        expect($observation)->not->toBeNull();
        expect($observation?->source)->toBe('open-meteo');
        expect((float) $observation?->temperature_c)->toBe(12.4);
        expect($observation?->humidity_percent)->toBe(81);
        expect($observation?->wind_direction)->toBe('S');
        expect($observation?->weather_condition)->toBe('rain');
        expect($observation?->is_raining)->toBeTrue();
        expect($observation?->raw_data['privacy'])->toBe('public_coordinates_rounded_to_2_decimals');

        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'latitude=48.85')
            && str_contains((string) $request->url(), 'longitude=2.35')
            && ! str_contains((string) $request->url(), '48.856613'));
    });

    it('summarizes environmental data and acoustic activity by weather condition', function () {
        $sound = Sound::factory()->create();

        EnvironmentalObservation::query()->create([
            'sound_id' => $sound->id,
            'season' => 'spring',
            'time_of_day' => 'dawn',
            'temperature_c' => 12.4,
            'humidity_percent' => 81,
            'wind_speed_kmh' => 10.2,
            'weather_condition' => 'rain',
            'source' => 'open-meteo',
        ]);

        ScientificMetric::query()->create([
            'measurable_type' => Sound::class,
            'measurable_id' => $sound->id,
            'metric_type' => 'acoustic_activity_score',
            'granularity' => 'overall',
            'value' => 72.5,
            'computed_at' => now(),
            'sample_size' => 1,
            'status' => 'complete',
        ]);

        $overview = (new ScientificStatsService())->getEnvironmentalOverview();

        expect($overview['coverage']['percentage'])->toBe(100.0);
        expect($overview['sources'][0]['source'])->toBe('open-meteo');
        expect($overview['weather_conditions'][0]['condition'])->toBe('rain');
        expect($overview['averages']['temperature_c'])->toBe(12.4);
        expect($overview['activity_by_weather_condition'][0]['mean_acoustic_activity_score'])->toBe(72.5);
    });
});
