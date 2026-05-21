<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ArborisisPoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArborisisPointFactory extends Factory
{
    protected $model = ArborisisPoint::class;

    public function definition(): array
    {
        $lat = $this->faker->latitude(41, 51);
        $lng = $this->faker->longitude(-5, 10);

        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'slug' => fn (array $attributes) => \Illuminate\Support\Str::slug($attributes['title']) . '-' . uniqid(),
            'description' => $this->faker->paragraph(),
            'latitude' => $lat,
            'longitude' => $lng,
            'approximate_latitude' => round($lat, 2),
            'approximate_longitude' => round($lng, 2),
            'visibility_status' => 'public',
            'moderation_status' => ModerationStatus::Pending,
            'category' => $this->faker->randomElement(ArborisisCategory::cases()),
            'tags' => $this->faker->words(3),
            'difficulty_level' => $this->faker->numberBetween(1, 5),
            'nature_sensitivity_level' => NatureSensitivityLevel::Normal,
            'recommended_time' => $this->faker->randomElement(['Aube', 'Matin', 'Midi', 'Soir', 'Nuit']),
            'audio_environment_type' => $this->faker->word(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => ModerationStatus::Approved,
            'approved_at' => now(),
        ]);
    }
}
