<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\SoundListened;
use App\Models\SoundListen;
use App\Services\Gamification\AchievementService;

class CheckAchievementsOnSoundListened
{
    public function handle(SoundListened $event): void
    {
        $user = $event->user;

        // Approximate total listened minutes from SoundListen records
        $totalSeconds = SoundListen::where('user_id', $user->id)->sum('listened_seconds');
        $totalMinutes = (int) round($totalSeconds / 60);

        app(AchievementService::class)->checkAndUnlock($user, 'listen_duration', [
            'minutes' => $totalMinutes,
        ]);
    }
}
