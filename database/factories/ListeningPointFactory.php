<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ListeningPoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ListeningPointFactory extends Factory
{
    protected $model = ListeningPoint::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);
        $lat = fake()->latitude(43, 49);
        $lng = fake()->longitude(-5, 8);

        return [
            'creator_user_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title.'-'.uniqid()),
            'description' => fake()->paragraph(),
            'exact_latitude' => $lat,
            'exact_longitude' => $lng,
            'public_latitude' => round($lat, 2),
            'public_longitude' => round($lng, 2),
            'habitat_type' => fake()->randomElement(['forest', 'wetland', 'river', 'meadow', 'ocean', 'mountain', 'urban_nature']),
            'nature_sensitivity_level' => NatureSensitivityLevel::Normal,
            'moderation_status' => ModerationStatus::Pending,
            'recordings_count' => 0,
            'species_detected_count' => 0,
            'stats_cache' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'moderation_status' => ModerationStatus::Approved,
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }
}
