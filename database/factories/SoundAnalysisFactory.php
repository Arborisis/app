<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisStatus;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoundAnalysisFactory extends Factory
{
    protected $model = SoundAnalysis::class;

    public function definition(): array
    {
        return [
            'sound_id' => Sound::factory(),
            'status' => AnalysisStatus::COMPLETED,
            'features_json' => [
                'duration_seconds' => 120.5,
                'sample_rate' => 22050,
                'channels' => 1,
                'rms_db' => -20.5,
                'zero_crossing_rate' => 0.1,
                'spectral_centroid' => 2500.0,
                'spectral_rolloff' => 5000.0,
                'spectral_bandwidth' => 1500.0,
                'spectral_flatness' => 0.1,
                'tempo_bpm' => 120.0,
                'event_density' => 0.5,
            ],
            'parameters_json' => ['n_fft' => 2048],
            'processed_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisStatus::PENDING,
            'processed_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AnalysisStatus::FAILED,
            'failed_reason' => 'Test failure',
        ]);
    }
}
