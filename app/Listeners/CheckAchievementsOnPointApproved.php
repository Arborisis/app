<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ArborisisPointApproved;
use App\Services\Gamification\AchievementService;

class CheckAchievementsOnPointApproved
{
    public function handle(ArborisisPointApproved $event): void
    {
        app(AchievementService::class)->checkAndUnlock($event->point->user, 'points_accepted');
    }
}
