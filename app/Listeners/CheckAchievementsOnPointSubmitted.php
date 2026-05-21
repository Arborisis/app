<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ArborisisPointSubmitted;
use App\Services\Gamification\AchievementService;

class CheckAchievementsOnPointSubmitted
{
    public function handle(ArborisisPointSubmitted $event): void
    {
        app(AchievementService::class)->checkAndUnlock($event->point->user, 'first_point_created');
    }
}
