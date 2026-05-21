<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ArborisisPointVisited;
use App\Services\Gamification\AchievementService;

class CheckAchievements
{
    public function handle(ArborisisPointVisited $event): void
    {
        $user = $event->visit->user;

        app(AchievementService::class)->checkAndUnlock($user, 'first_visit');
        app(AchievementService::class)->checkAndUnlock($user, 'visit_count');
    }
}
