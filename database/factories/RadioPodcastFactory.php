<?php

namespace Database\Factories;

use App\Enums\RadioPodcastStatus;
use App\Models\RadioPodcast;
use Illuminate\Database\Eloquent\Factories\Factory;

class RadioPodcastFactory extends Factory
{
    protected $model = RadioPodcast::class;

    public function definition(): array
    {
        return [
            'status' => RadioPodcastStatus::Pending,
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'theme' => null,
            'script_json' => null,
            'research_json' => null,
            'tts_text' => null,
            'voice_provider' => 'elevenlabs',
            'voice_id' => null,
            'disk' => 'radio_cache',
            'path' => null,
            'mime_type' => null,
            'size_bytes' => null,
            'target_duration_seconds' => 180,
            'actual_duration_seconds' => null,
            'sound_ids' => null,
            'generation_cost_cents' => null,
            'error_message' => null,
            'scheduled_at' => null,
            'published_at' => null,
            'failed_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RadioPodcastStatus::Published,
            'published_at' => now(),
            'actual_duration_seconds' => 150,
            'path' => 'podcasts/' . $this->faker->uuid() . '.mp3',
        ]);
    }
}
