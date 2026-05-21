<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ProfileUpdated;
use App\Services\Gamification\AchievementService;

class CheckAchievementsOnProfileUpdated
{
    public function handle(ProfileUpdated $event): void
    {
        app(AchievementService::class)->checkAndUnlock($event->user, 'complete_profile');
    }
}
