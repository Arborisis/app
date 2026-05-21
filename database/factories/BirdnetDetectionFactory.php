<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\BirdnetDetection;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Database\Eloquent\Factories\Factory;

class BirdnetDetectionFactory extends Factory
{
    protected $model = BirdnetDetection::class;

    public function definition(): array
    {
        return [
            'sound_analysis_id' => SoundAnalysis::factory(),
            'sound_id' => Sound::factory(),
            'scientific_name' => fake()->word().' '.fake()->word(),
            'common_name' => fake()->word(),
            'confidence' => fake()->randomFloat(3, 0.1, 0.99),
            'start_time' => fake()->randomFloat(2, 0, 30),
            'end_time' => fake()->randomFloat(2, 30, 60),
            'frequency_min' => fake()->numberBetween(100, 1000),
            'frequency_max' => fake()->numberBetween(2000, 8000),
            'is_validated' => false,
            'validated_by' => null,
        ];
    }

    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_validated' => true,
        ]);
    }
}
