<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\ArborisisPointVisited;
use App\Services\Gamification\QuestService;

class UpdateQuestProgress
{
    public function handle(ArborisisPointVisited $event): void
    {
        $visit = $event->visit;
        $user = $visit->user;
        $point = $visit->arborisisPoint;

        $questService = app(QuestService::class);

        // Update visit_points quests
        $questService->updateProgress($user, ObjectiveType::VisitPoints);

        // Update visit_category quests
        if ($point) {
            $questService->updateProgress($user, ObjectiveType::VisitCategory, 1, [
                'category' => $point->category->value,
            ]);
        }
    }
}
