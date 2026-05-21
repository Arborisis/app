<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\User;
use Carbon\Carbon;

class StreakService
{
    public function updateLoginStreak(User $user): int
    {
        $lastActivity = $user->last_activity_at ? Carbon::parse($user->last_activity_at) : null;
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        if ($lastActivity === null) {
            $currentStreak = 1;
        } elseif ($lastActivity->isSameDay($today)) {
            // Already logged in today, keep current streak
            $currentStreak = $user->current_streak;
        } elseif ($lastActivity->isSameDay($yesterday)) {
            $currentStreak = $user->current_streak + 1;
        } else {
            $currentStreak = 1;
        }

        $longestStreak = max($user->longest_streak, $currentStreak);

        $user->update([
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'last_activity_at' => now(),
        ]);

        return $currentStreak;
    }
}
