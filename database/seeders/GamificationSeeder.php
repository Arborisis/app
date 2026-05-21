<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AchievementCategory;
use App\Enums\MedalCategory;
use App\Enums\MedalRarity;
use App\Enums\ObjectiveType;
use App\Enums\QuestType;
use App\Models\Achievement;
use App\Models\Medal;
use App\Models\Quest;
use Illuminate\Database\Seeder;

class GamificationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAchievements();
        $this->seedMedals();
        $this->seedQuests();
    }

    private function seedAchievements(): void
    {
        $achievements = [
            [
                'title' => 'Premier pas',
                'slug' => 'premier-pas',
                'description' => 'Effectuer ta première visite d\'un point Arborisis.',
                'category' => AchievementCategory::Exploration,
                'points' => 10,
                'condition_type' => 'first_visit',
                'condition_payload' => [],
            ],
            [
                'title' => 'Premier son',
                'slug' => 'premier-son',
                'description' => 'Publier ton premier enregistrement sonore.',
                'category' => AchievementCategory::Creation,
                'points' => 30,
                'condition_type' => 'first_sound',
                'condition_payload' => [],
            ],
            [
                'title' => 'Premier Arborisis',
                'slug' => 'premier-arborisis',
                'description' => 'Proposer ton premier point sur la carte.',
                'category' => AchievementCategory::Creation,
                'points' => 50,
                'condition_type' => 'first_point_created',
                'condition_payload' => [],
            ],
            [
                'title' => 'Validé par la communauté',
                'slug' => 'valide-par-la-communaute',
                'description' => 'Voir ton premier point accepté par la modération.',
                'category' => AchievementCategory::Creation,
                'points' => 50,
                'condition_type' => 'points_accepted',
                'condition_payload' => [],
            ],
            [
                'title' => '7 jours d\'exploration',
                'slug' => '7-jours-exploration',
                'description' => 'Te connecter et explorer pendant 7 jours différents.',
                'category' => AchievementCategory::Milestone,
                'points' => 100,
                'condition_type' => 'login_streak',
                'condition_payload' => ['days' => 7],
            ],
            [
                'title' => 'Écoute profonde',
                'slug' => 'ecoute-profonde',
                'description' => 'Écouter 30 minutes de sons naturels au total.',
                'category' => AchievementCategory::Exploration,
                'points' => 50,
                'condition_type' => 'listen_duration',
                'condition_payload' => ['minutes' => 30],
            ],
            [
                'title' => 'Explorateur local',
                'slug' => 'explorateur-local',
                'description' => 'Visiter 10 points dans une même région.',
                'category' => AchievementCategory::Exploration,
                'points' => 75,
                'condition_type' => 'visit_count',
                'condition_payload' => ['count' => 10],
            ],
            [
                'title' => 'Naturaliste respectueux',
                'slug' => 'naturaliste-respectueux',
                'description' => 'Accepter la charte de respect de la nature.',
                'category' => AchievementCategory::Nature,
                'points' => 25,
                'condition_type' => 'complete_profile',
                'condition_payload' => ['field' => 'nature_charter_accepted'],
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::firstOrCreate(['slug' => $achievement['slug']], $achievement);
        }
    }

    private function seedMedals(): void
    {
        $medals = [
            [
                'name' => 'Première feuille',
                'slug' => 'premiere-feuille',
                'description' => 'Effectuer ta première visite sur la carte.',
                'rarity' => MedalRarity::Bronze,
                'category' => MedalCategory::Exploration,
                'unlock_condition_type' => 'first_visit',
                'unlock_condition_value' => [],
            ],
            [
                'name' => 'Écho forestier',
                'slug' => 'echo-forestier',
                'description' => 'Écouter 10 sons en forêt.',
                'rarity' => MedalRarity::Silver,
                'category' => MedalCategory::Exploration,
                'unlock_condition_type' => 'listen_count',
                'unlock_condition_value' => ['count' => 10],
            ],
            [
                'name' => 'Gardien du silence',
                'slug' => 'gardien-du-silence',
                'description' => 'Signaler correctement 5 points problématiques.',
                'rarity' => MedalRarity::Silver,
                'category' => MedalCategory::Respect,
                'unlock_condition_type' => 'report_valid_count',
                'unlock_condition_value' => ['count' => 5],
            ],
            [
                'name' => 'Cartographe Arborisis',
                'slug' => 'cartographe-arborisis',
                'description' => 'Créer 10 points validés par la modération.',
                'rarity' => MedalRarity::Gold,
                'category' => MedalCategory::Creation,
                'unlock_condition_type' => 'create_point_count',
                'unlock_condition_value' => ['count' => 10],
            ],
            [
                'name' => 'Marcheur nocturne',
                'slug' => 'marcheur-nocturne',
                'description' => 'Visiter un point de catégorie ambiance nocturne.',
                'rarity' => MedalRarity::Bronze,
                'category' => MedalCategory::Exploration,
                'unlock_condition_type' => 'visit_category_count',
                'unlock_condition_value' => ['category' => 'night_ambience', 'count' => 1],
            ],
            [
                'name' => 'Ami des oiseaux',
                'slug' => 'ami-des-oiseaux',
                'description' => 'Visiter 5 points de catégorie Oiseaux.',
                'rarity' => MedalRarity::Silver,
                'category' => MedalCategory::Exploration,
                'unlock_condition_type' => 'visit_category_count',
                'unlock_condition_value' => ['category' => 'birds', 'count' => 5],
            ],
            [
                'name' => 'Racine d\'or',
                'slug' => 'racine-d-or',
                'description' => 'Contribution exceptionnelle validée par un admin.',
                'rarity' => MedalRarity::Legendary,
                'category' => MedalCategory::Special,
                'unlock_condition_type' => 'admin_awarded',
                'unlock_condition_value' => [],
                'is_secret' => true,
            ],
        ];

        foreach ($medals as $medal) {
            Medal::firstOrCreate(['slug' => $medal['slug']], $medal);
        }
    }

    private function seedQuests(): void
    {
        $quests = [
            [
                'title' => 'Première exploration',
                'description' => 'Visiter 1 point Arborisis validé.',
                'type' => QuestType::Discovery,
                'category' => 'beginner',
                'objective_type' => ObjectiveType::VisitPoints,
                'objective_target' => 1,
                'reward_xp' => 15,
            ],
            [
                'title' => 'Explorateur forestier',
                'description' => 'Visiter 5 points de catégorie Forêt.',
                'type' => QuestType::Discovery,
                'category' => 'exploration',
                'objective_type' => ObjectiveType::VisitCategory,
                'objective_target' => 5,
                'objective_payload' => ['category' => 'forest'],
                'reward_xp' => 100,
            ],
            [
                'title' => 'Oreille attentive',
                'description' => 'Écouter 10 sons naturels.',
                'type' => QuestType::Listening,
                'category' => 'listening',
                'objective_type' => ObjectiveType::ListenSound,
                'objective_target' => 10,
                'reward_xp' => 50,
            ],
            [
                'title' => 'Créateur de lieux',
                'description' => 'Proposer 1 point Arborisis validé.',
                'type' => QuestType::Creation,
                'category' => 'creation',
                'objective_type' => ObjectiveType::CreatePoint,
                'objective_target' => 1,
                'reward_xp' => 50,
            ],
            [
                'title' => 'Gardien de la nature',
                'description' => 'Signaler correctement un point problématique.',
                'type' => QuestType::NatureRespect,
                'category' => 'moderation',
                'objective_type' => ObjectiveType::ReportValidIssue,
                'objective_target' => 1,
                'reward_xp' => 15,
            ],
            [
                'title' => 'Collectionneur d\'ambiances',
                'description' => 'Visiter un point Eau, Forêt, Oiseaux et Vent.',
                'type' => QuestType::Discovery,
                'category' => 'collection',
                'objective_type' => ObjectiveType::VisitPoints,
                'objective_target' => 4,
                'reward_xp' => 80,
            ],
            [
                'title' => 'Marche douce',
                'description' => 'Effectuer 3 visites sans signalement anti-cheat.',
                'type' => QuestType::NatureRespect,
                'category' => 'integrity',
                'objective_type' => ObjectiveType::VisitPoints,
                'objective_target' => 3,
                'reward_xp' => 30,
            ],
            [
                'title' => 'Archiviste sonore',
                'description' => 'Publier 5 sons géolocalisés validés.',
                'type' => QuestType::Creation,
                'category' => 'creation',
                'objective_type' => ObjectiveType::UploadSound,
                'objective_target' => 5,
                'reward_xp' => 150,
            ],
        ];

        foreach ($quests as $quest) {
            Quest::firstOrCreate([
                'title' => $quest['title'],
                'type' => $quest['type'],
            ], array_merge($quest, [
                'starts_at' => null,
                'ends_at' => null,
                'is_repeatable' => false,
                'is_active' => true,
            ]));
        }
    }
}
