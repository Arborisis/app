<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Sound;
use App\Models\SoundFile;
use Illuminate\Database\Eloquent\Factories\Factory;

class SoundFileFactory extends Factory
{
    protected $model = SoundFile::class;

    public function definition(): array
    {
        return [
            'sound_id' => Sound::factory(),
            'original_name' => fake()->word() . '.wav',
            'stored_name' => fake()->uuid() . '.wav',
            'path' => 'audio/1/' . fake()->uuid() . '.wav',
            'mime_type' => 'audio/wav',
            'size_bytes' => fake()->numberBetween(100000, 10000000),
            'disk' => 'audio',
        ];
    }
}
