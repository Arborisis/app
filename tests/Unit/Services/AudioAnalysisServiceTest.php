<?php

declare(strict_types=1);

use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Services\AudioAnalysis\AudioAnalysisService;
use App\Services\AudioAnalysis\FeatureExtractorService;
use App\Services\AudioAnalysis\PythonRunnerService;

beforeEach(function () {
    $this->sound = Sound::factory()->create();
    $this->analysis = SoundAnalysis::factory()->create([
        'sound_id' => $this->sound->id,
        'features_json' => [
            'duration_seconds' => 120.5,
            'rms_db' => -20.5,
            'rms_std' => 2.5,
            'zero_crossing_rate' => 0.1,
            'zcr_std' => 0.02,
            'spectral_centroid' => 2500.0,
            'spectral_rolloff' => 5000.0,
            'spectral_bandwidth' => 1500.0,
            'spectral_flatness' => 0.1,
            'tempo_bpm' => 120.0,
            'beat_count' => 240,
            'event_density' => 0.5,
            'mfcc' => [
                'mfcc_1' => ['mean' => -100.0, 'std' => 10.0, 'min' => -120.0, 'max' => -80.0],
            ],
            'chroma' => [
                'chroma_1' => ['mean' => 0.5, 'std' => 0.1, 'min' => 0.0, 'max' => 1.0],
            ],
        ],
    ]);
});

describe('FeatureExtractorService', function () {
    it('extracts temporal features', function () {
        $service = new FeatureExtractorService();
        $features = $service->getTemporalFeatures($this->analysis);

        expect($features)->toHaveKey('zero_crossing_rate');
        expect($features)->toHaveKey('rms_db');
        expect($features['duration_seconds'])->toBe(120.5);
    });

    it('extracts spectral features', function () {
        $service = new FeatureExtractorService();
        $features = $service->getSpectralFeatures($this->analysis);

        expect($features)->toHaveKey('spectral_centroid');
        expect($features)->toHaveKey('spectral_bandwidth');
    });

    it('returns public summary with key metrics', function () {
        $service = new FeatureExtractorService();
        $summary = $service->getPublicSummary($this->analysis);

        expect($summary)->toHaveKeys(['duration_seconds', 'rms_mean', 'zcr_mean', 'centroid_mean']);
        expect($summary['duration_seconds'])->toBe(120.5);
        expect($summary['rms_mean'])->toBe(-20.5);
    });
});

describe('AudioAnalysisService', function () {
    it('returns existing analysis via getOrCreateAnalysis', function () {
        $mockRunner = Mockery::mock(PythonRunnerService::class);
        $service = new AudioAnalysisService($mockRunner);

        $result = $service->getOrCreateAnalysis($this->sound);

        expect($result->id)->toBe($this->analysis->id);
    });

    it('creates new analysis when none exists', function () {
        $sound = Sound::factory()->create();
        $mockRunner = Mockery::mock(PythonRunnerService::class);
        $service = new AudioAnalysisService($mockRunner);

        $result = $service->getOrCreateAnalysis($sound);

        expect($result->sound_id)->toBe($sound->id);
        expect($result->status->value)->toBe('pending');
    });

    it('exports features as json', function () {
        $mockRunner = Mockery::mock(PythonRunnerService::class);
        $service = new AudioAnalysisService($mockRunner);

        $export = $service->exportFeatures($this->analysis, 'json');

        expect($export['mime'])->toBe('application/json');
        expect($export['filename'])->toBe("features_{$this->sound->id}.json");
        expect(json_decode($export['content'], true))->toHaveKey('duration_seconds');
    });

    it('exports features as csv', function () {
        $mockRunner = Mockery::mock(PythonRunnerService::class);
        $service = new AudioAnalysisService($mockRunner);

        $export = $service->exportFeatures($this->analysis, 'csv');

        expect($export['mime'])->toBe('text/csv');
        expect($export['filename'])->toBe("features_{$this->sound->id}.csv");
        expect(str_contains($export['content'], 'feature,value'))->toBeTrue();
    });

    it('throws on unsupported export format', function () {
        $mockRunner = Mockery::mock(PythonRunnerService::class);
        $service = new AudioAnalysisService($mockRunner);

        $service->exportFeatures($this->analysis, 'pdf');
    })->throws(\InvalidArgumentException::class);
});
