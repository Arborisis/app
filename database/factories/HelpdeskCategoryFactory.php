<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HelpdeskCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class HelpdeskCategoryFactory extends Factory
{
    protected $model = HelpdeskCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug' => \Illuminate\Support\Str::slug($name),
            'name' => ucfirst($name),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(['emerald', 'cyan', 'amber', 'red', 'firefly']),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
