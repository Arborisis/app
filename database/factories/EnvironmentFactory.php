<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EnvironmentType;
use App\Models\Environment;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnvironmentFactory extends Factory
{
    protected $model = Environment::class;

    public function definition(): array
    {
        $type = fake()->randomElement(EnvironmentType::cases());

        return [
            'name' => $type->label(),
            'slug' => $type->value.'-'.fake()->unique()->numberBetween(1, 100000),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'icon' => 'leaf',
            'order' => fake()->numberBetween(1, 100),
        ];
    }
}
