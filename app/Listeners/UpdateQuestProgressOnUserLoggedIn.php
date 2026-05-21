<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\UserLoggedIn;
use App\Services\Gamification\QuestService;
use App\Services\Gamification\StreakService;

class UpdateQuestProgressOnUserLoggedIn
{
    public function handle(UserLoggedIn $event): void
    {
        $user = $event->user;

        $streak = app(StreakService::class)->updateLoginStreak($user);

        app(QuestService::class)->updateProgress($user, ObjectiveType::LoginStreak, 1, [
            'streak' => $streak,
        ]);
    }
}
