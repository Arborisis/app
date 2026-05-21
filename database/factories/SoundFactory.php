<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LicenseType;
use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use App\Models\Category;
use App\Models\Environment;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SoundFactory extends Factory
{
    protected $model = Sound::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'environment_id' => Environment::factory(),
            'title' => $title,
            'slug' => Str::slug($title.'-'.uniqid()),
            'description' => fake()->paragraph(),
            'recorded_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'duration' => fake()->numberBetween(30, 600),
            'equipment' => fake()->words(3, true),
            'license' => LicenseType::CcBy,
            'visibility' => SoundVisibility::Public,
            'status' => SoundStatus::Published,

        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SoundStatus::Draft,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => SoundVisibility::Private,
        ]);
    }
}
