<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ListeningPoint;
use App\Models\Sound;
use App\Models\SoundLocation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListeningPointSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            return;
        }

        // Créer quelques points d'écoute de test
        $points = [
            [
                'title' => 'Forêt de Fontainebleau — Carrefour de la Croix',
                'exact_latitude' => 48.4021,
                'exact_longitude' => 2.6845,
                'public_latitude' => 48.40,
                'public_longitude' => 2.68,
                'habitat_type' => 'forest',
            ],
            [
                'title' => 'Marais de Séné — Observatoire Ouest',
                'exact_latitude' => 47.6201,
                'exact_longitude' => -2.7391,
                'public_latitude' => 47.62,
                'public_longitude' => -2.74,
                'habitat_type' => 'wetland',
            ],
            [
                'title' => 'Vallée de la Loire — Bords de fleuve',
                'exact_latitude' => 47.3941,
                'exact_longitude' => 0.6888,
                'public_latitude' => 47.39,
                'public_longitude' => 0.69,
                'habitat_type' => 'river',
            ],
        ];

        foreach ($points as $data) {
            $point = ListeningPoint::create([
                'creator_user_id' => $user->id,
                'title' => $data['title'],
                'exact_latitude' => $data['exact_latitude'],
                'exact_longitude' => $data['exact_longitude'],
                'public_latitude' => $data['public_latitude'],
                'public_longitude' => $data['public_longitude'],
                'public_accuracy_meters' => 1000,
                'habitat_type' => $data['habitat_type'],
                'moderation_status' => ModerationStatus::Approved,
                'nature_sensitivity_level' => NatureSensitivityLevel::Normal,
                'approved_at' => now(),
                'approved_by' => $user->id,
            ]);

            $this->command->info("Created listening point: {$point->title}");
        }
    }
}
