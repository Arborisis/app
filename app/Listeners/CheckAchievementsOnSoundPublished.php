<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SoundPublished;
use App\Services\Gamification\AchievementService;

class CheckAchievementsOnSoundPublished
{
    public function handle(SoundPublished $event): void
    {
        $user = $event->sound->user;

        if (! $user) {
            return;
        }

        app(AchievementService::class)->checkAndUnlock($user, 'first_sound');
    }
}
