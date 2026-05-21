<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\UserLoggedIn;
use App\Services\Gamification\AchievementService;

class CheckAchievementsOnUserLoggedIn
{
    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;

        app(AchievementService::class)->checkAndUnlock($user, 'login_streak', [
            'streak' => $user->current_streak,
        ]);
    }
}
