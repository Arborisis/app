<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RadioProductionPreset;
use App\Models\RadioChannel;
use Illuminate\Database\Seeder;

class RadioChannelSeeder extends Seeder
{
    public function run(): void
    {
        RadioChannel::query()->updateOrCreate(
            ['slug' => 'main'],
            [
                'name' => 'Arborisis Radio',
                'mount_path' => '/arborisis.mp3',
                'color' => '#3c8c5c',
                'description' => 'Le flux principal : field recordings et sons de nature en continu.',
                'vibe' => [
                    'tone' => 'naturaliste, poétique, calme',
                    'audience' => 'auditeurs de field recording, amoureux de la nature',
                    'keywords' => ['forêt', 'oiseaux', 'paysage sonore', 'immersion'],
                ],
                'playlist_source' => null,
                'production_preset' => RadioProductionPreset::EmissionCinematic->value,
                'is_active' => true,
                'sort_order' => 0,
            ],
        );
    }
}
