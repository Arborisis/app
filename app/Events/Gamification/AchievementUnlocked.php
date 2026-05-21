<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\UserAchievement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementUnlocked
{
    use Dispatchable, SerializesModels;

    public function __construct(public UserAchievement $userAchievement)
    {
    }
}
