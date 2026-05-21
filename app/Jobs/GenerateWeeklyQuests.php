<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\ObjectiveType;
use App\Enums\QuestType;
use App\Models\Quest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateWeeklyQuests implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Quest::where('type', QuestType::Weekly)
            ->where('ends_at', '<', now())
            ->update(['is_active' => false]);

        $existingThisWeek = Quest::where('type', QuestType::Weekly)
            ->where('starts_at', '>=', now()->startOfWeek())
            ->exists();

        if ($existingThisWeek) {
            return;
        }

        $weeklyQuests = [
            [
                'title' => 'Explorateur forestier',
                'description' => 'Visiter 5 points de catégorie Forêt cette semaine.',
                'objective_type' => ObjectiveType::VisitCategory,
                'objective_target' => 5,
                'objective_payload' => ['category' => 'forest'],
                'reward_xp' => 100,
            ],
            [
                'title' => 'Collectionneur d\'ambiances',
                'description' => 'Visiter un point Eau, Forêt, Oiseaux et Vent cette semaine.',
                'objective_type' => ObjectiveType::VisitPoints,
                'objective_target' => 4,
                'reward_xp' => 150,
            ],
        ];

        foreach ($weeklyQuests as $quest) {
            Quest::create([
                ...$quest,
                'type' => QuestType::Weekly,
                'category' => 'weekly',
                'starts_at' => now()->startOfWeek(),
                'ends_at' => now()->endOfWeek(),
                'is_repeatable' => false,
                'is_active' => true,
            ]);
        }
    }
}
