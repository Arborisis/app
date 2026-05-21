<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ArborisisPoint;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SeedDemoArborisisPoints extends Command
{
    protected $signature = 'arborisis:seed-demo {count=15}';
    protected $description = 'Create demo Arborisis points for production testing';

    public function handle(): int
    {
        $count = (int) $this->argument('count');
        $admin = User::whereIn('role', ['admin', 'moderator'])->first()
            ?? User::first();

        if (! $admin) {
            $this->error('No user found. Create a user first.');
            return 1;
        }

        $demoPoints = [
            ['title' => 'Clairière aux chouettes hulottes', 'lat' => 48.8566, 'lng' => 2.3522, 'cat' => ArborisisCategory::Birds, 'sens' => NatureSensitivityLevel::SensitiveSpecies],
            ['title' => 'Bord de Seine à l\'aube', 'lat' => 48.8589, 'lng' => 2.35, 'cat' => ArborisisCategory::Water, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Forêt de Fontainebleau — Carrefour de l\'Éléphant', 'lat' => 48.4, 'lng' => 2.7, 'cat' => ArborisisCategory::Forest, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Marais de la Bièvre', 'lat' => 48.76, 'lng' => 2.27, 'cat' => ArborisisCategory::Water, 'sens' => NatureSensitivityLevel::Fragile],
            ['title' => 'Point de rencontre des naturalistes', 'lat' => 48.85, 'lng' => 2.34, 'cat' => ArborisisCategory::MeetingPoint, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Spot calme — Vallée de Chevreuse', 'lat' => 48.71, 'lng' => 2.04, 'cat' => ArborisisCategory::QuietSpot, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Zone pédagogique — Arboretum', 'lat' => 48.82, 'lng' => 2.33, 'cat' => ArborisisCategory::EducationalZone, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Champ de criquets', 'lat' => 43.6, 'lng' => 1.44, 'cat' => ArborisisCategory::Insects, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Vent dans les dunes du Pilat', 'lat' => 44.58, 'lng' => -1.21, 'cat' => ArborisisCategory::Wind, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Ambiance nocturne — Lac du Bourget', 'lat' => 45.76, 'lng' => 5.86, 'cat' => ArborisisCategory::NightAmbience, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Nid de hérons cendrés', 'lat' => 47.32, 'lng' => 5.04, 'cat' => ArborisisCategory::Birds, 'sens' => NatureSensitivityLevel::SensitiveSpecies],
            ['title' => 'Cascade du Hérisson', 'lat' => 46.62, 'lng' => 5.82, 'cat' => ArborisisCategory::Water, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Forêt des Cévennes — Chênes centenaires', 'lat' => 44.2, 'lng' => 3.6, 'cat' => ArborisisCategory::Forest, 'sens' => NatureSensitivityLevel::Normal],
            ['title' => 'Point d\'observation — Camargue', 'lat' => 43.53, 'lng' => 4.43, 'cat' => ArborisisCategory::Birds, 'sens' => NatureSensitivityLevel::Fragile],
            ['title' => 'Grotte aux chauves-souris', 'lat' => 44.9, 'lng' => 1.2, 'cat' => ArborisisCategory::NightAmbience, 'sens' => NatureSensitivityLevel::SensitiveSpecies],
        ];

        $created = 0;
        foreach (array_slice($demoPoints, 0, $count) as $data) {
            $coords = ArborisisPoint::obscure($data['lat'], $data['lng']);

            $point = ArborisisPoint::create([
                'user_id' => $admin->id,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']) . '-' . uniqid(),
                'description' => 'Un lieu remarquable pour l\'écoute et l\'observation de la nature.',
                'latitude' => $data['sens']->requiresApproximateLocation() ? $coords['approximate_latitude'] : $data['lat'],
                'longitude' => $data['sens']->requiresApproximateLocation() ? $coords['approximate_longitude'] : $data['lng'],
                'approximate_latitude' => $coords['approximate_latitude'],
                'approximate_longitude' => $coords['approximate_longitude'],
                'visibility_status' => 'public',
                'moderation_status' => ModerationStatus::Approved,
                'category' => $data['cat'],
                'tags' => ['démo', 'nature', $data['cat']->label()],
                'difficulty_level' => rand(1, 3),
                'nature_sensitivity_level' => $data['sens'],
                'recommended_time' => rand(0, 1) ? 'Aube' : 'Toute la journée',
                'audio_environment_type' => 'Nature sauvage',
                'approved_at' => now(),
                'approved_by' => $admin->id,
            ]);

            $created++;
            $this->info("Created: {$point->title} [{$point->moderation_status->label()}]");
        }

        $this->info("\n✅ {$created} demo points created successfully.");
        return 0;
    }
}
