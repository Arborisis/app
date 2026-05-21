<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ObjectiveType;
use App\Enums\QuestType;
use App\Models\Quest;
use App\Services\Gamification\AiQuestGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateDailyQuests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Deactivate old daily quests
        Quest::where('type', QuestType::Daily)
            ->where('ends_at', '<', now())
            ->update(['is_active' => false]);

        // Create new daily quests if none exists for today
        $existingToday = Quest::where('type', QuestType::Daily)
            ->whereDate('starts_at', today())
            ->exists();

        if ($existingToday) {
            return;
        }

        // Try AI-generated quests first
        $result = app(AiQuestGeneratorService::class)->generateDailyQuests();
        $aiQuests = $result['quests'] ?? [];
        $theme = $result['theme'] ?? null;

        if (! empty($aiQuests)) {
            foreach ($aiQuests as $questData) {
                Quest::create([
                    'title' => $questData['title'],
                    'description' => $questData['description'],
                    'type' => QuestType::Daily,
                    'category' => $questData['category'] ?? 'daily',
                    'objective_type' => ObjectiveType::from($questData['objective_type']),
                    'objective_target' => $questData['objective_target'],
                    'objective_payload' => $questData['objective_payload'] ?? null,
                    'reward_xp' => $questData['reward_xp'],
                    'starts_at' => now(),
                    'ends_at' => now()->endOfDay(),
                    'is_repeatable' => false,
                    'is_active' => true,
                ]);
            }

            if (! empty($theme)) {
                Cache::put('daily_quest_theme', $theme, now()->endOfDay());
            }

            Log::info('GenerateDailyQuests: AI quests created', ['count' => count($aiQuests)]);

            return;
        }

        // Fallback to static daily quests if AI fails
        $this->createFallbackQuests();
    }

    private function createFallbackQuests(): void
    {
        $dailyQuests = [
            [
                'title' => 'Première exploration',
                'description' => 'Visiter 1 point Arborisis validé aujourd\'hui.',
                'objective_type' => ObjectiveType::VisitPoints,
                'objective_target' => 1,
                'reward_xp' => 15,
            ],
            [
                'title' => 'Oreille attentive',
                'description' => 'Visiter 3 points différents aujourd\'hui.',
                'objective_type' => ObjectiveType::VisitPoints,
                'objective_target' => 3,
                'reward_xp' => 30,
            ],
            [
                'title' => 'Écoute matinale',
                'description' => 'Écouter 2 sons publiés cette semaine.',
                'objective_type' => ObjectiveType::ListenSound,
                'objective_target' => 2,
                'reward_xp' => 20,
            ],
            [
                'title' => 'Partage sonore',
                'description' => 'Laisser un commentaire sur un son qui vous a touché.',
                'objective_type' => ObjectiveType::Comment,
                'objective_target' => 1,
                'reward_xp' => 15,
            ],
        ];

        $selected = $dailyQuests[array_rand($dailyQuests)];

        Quest::create([
            ...$selected,
            'type' => QuestType::Daily,
            'category' => 'daily',
            'starts_at' => now(),
            'ends_at' => now()->endOfDay(),
            'is_repeatable' => false,
            'is_active' => true,
        ]);

        Log::info('GenerateDailyQuests: fallback static quest created');
    }
}
