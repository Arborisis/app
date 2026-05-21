<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Events\Gamification\XpGained;
use App\Models\User;
use App\Models\XpEvent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class XpService
{
    private const DAILY_XP_KEY_PREFIX = 'arborisis:daily_xp:';

    public function award(User $user, int $amount, string $sourceType, ?int $sourceId = null, ?string $reason = null): ?XpEvent
    {
        if ($amount <= 0) {
            return null;
        }

        $dailyLimit = config('gamification.daily_xp_limit', 500);
        $dailyXp = $this->getDailyXp($user->id);

        if ($dailyXp >= $dailyLimit) {
            return null;
        }

        $awardedAmount = min($amount, $dailyLimit - $dailyXp);

        return DB::transaction(function () use ($user, $awardedAmount, $sourceType, $sourceId, $reason) {
            $event = XpEvent::create([
                'user_id' => $user->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'amount' => $awardedAmount,
                'reason' => $reason,
            ]);

            $user->increment('xp_total', $awardedAmount);
            $user->update([
                'last_activity_at' => now(),
            ]);

            $this->trackDailyXp($user->id, $awardedAmount);

            XpGained::dispatch($event);

            // Check for level up
            $this->recalculateLevel($user);

            return $event;
        });
    }

    public function recalculateLevel(User $user): void
    {
        $newLevel = $this->calculateLevelFromXp($user->xp_total);

        if ($newLevel !== $user->level) {
            $user->update([
                'level' => $newLevel,
            ]);
        }
    }

    public function calculateLevelFromXp(int $xp): int
    {
        // Simple formula: level = floor(sqrt(xp / 100)) + 1
        // Level 1: 0-99 XP
        // Level 2: 100-399 XP
        // Level 3: 400-899 XP
        return (int) floor(sqrt($xp / 100)) + 1;
    }

    public function xpForNextLevel(int $currentLevel): int
    {
        return (int) pow($currentLevel, 2) * 100;
    }

    private function getDailyXp(int $userId): int
    {
        $key = self::DAILY_XP_KEY_PREFIX . $userId . ':' . now()->format('Y-m-d');

        if (app()->environment('testing')) {
            return (int) Cache::get($key, 0);
        }

        return (int) Redis::get($key);
    }

    private function trackDailyXp(int $userId, int $amount): void
    {
        $key = self::DAILY_XP_KEY_PREFIX . $userId . ':' . now()->format('Y-m-d');
        if (app()->environment('testing')) {
            Cache::put($key, $this->getDailyXp($userId) + $amount, 86400);

            return;
        }

        Redis::incrby($key, $amount);
        Redis::expire($key, 86400);
    }
}
