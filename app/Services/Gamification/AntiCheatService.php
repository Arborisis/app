<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\ArborisisPoint;
use App\Models\ArborisisVisit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class AntiCheatService
{
    private const COOLDOWN_KEY_PREFIX = 'arborisis:cooldown:';
    private const DAILY_COUNT_KEY_PREFIX = 'arborisis:daily_visits:';

    public function validateVisit(
        User $user,
        ArborisisPoint $point,
        float $userLat,
        float $userLng,
        ?float $accuracy,
    ): array {
        $issues = [];
        $score = 100.0;

        // 1. Check if point is approved
        if (! $point->isApproved()) {
            $issues[] = 'point_not_approved';
            $score = 0;
        }

        // 2. Check GPS accuracy
        $geoService = app(GeoValidationService::class);
        if ($accuracy !== null && ! $geoService->isAccuracyAcceptable($accuracy)) {
            $issues[] = 'poor_accuracy';
            $score -= 30;
        }

        // 3. Check distance
        $distance = $geoService->getDistanceFromPoint($userLat, $userLng, $point);
        $maxRadius = config('gamification.check_in_radius', 100);
        if ($distance > $maxRadius) {
            $issues[] = 'too_far';
            $score = 0;
        }

        // 4. Check cooldown (Redis)
        if ($this->isCooldownActive($user->id, $point->id)) {
            $issues[] = 'cooldown_active';
            $score -= 40;
        }

        // 5. Check daily limit
        if ($this->isDailyLimitReached($user->id)) {
            $issues[] = 'daily_limit_reached';
            $score -= 50;
        }

        // 6. Check impossible speed
        if ($this->isImpossibleSpeed($user->id, $userLat, $userLng)) {
            $issues[] = 'impossible_speed';
            $score = max($score - 60, 0);
        }

        $finalScore = max($score, 0);
        $isValid = empty($issues) || $finalScore >= 50;

        return [
            'is_valid' => $isValid,
            'score' => $finalScore,
            'issues' => $issues,
            'distance' => $distance,
        ];
    }

    public function recordVisit(User $user, int $pointId): void
    {
        $cooldownSeconds = config('gamification.visit_cooldown', 3600);
        $redisKey = self::COOLDOWN_KEY_PREFIX . "{$user->id}:{$pointId}";
        if (app()->environment('testing')) {
            Cache::put($redisKey, '1', $cooldownSeconds);
        } else {
            Redis::setex($redisKey, $cooldownSeconds, '1');
        }

        $dailyKey = self::DAILY_COUNT_KEY_PREFIX . $user->id . ':' . now()->format('Y-m-d');
        if (app()->environment('testing')) {
            Cache::put($dailyKey, ((int) Cache::get($dailyKey, 0)) + 1, 86400);
        } else {
            Redis::incr($dailyKey);
            Redis::expire($dailyKey, 86400);
        }
    }

    public function isCooldownActive(int $userId, int $pointId): bool
    {
        $redisKey = self::COOLDOWN_KEY_PREFIX . "{$userId}:{$pointId}";

        if (app()->environment('testing')) {
            return Cache::has($redisKey);
        }

        return Redis::exists($redisKey) > 0;
    }

    public function isDailyLimitReached(int $userId): bool
    {
        $dailyKey = self::DAILY_COUNT_KEY_PREFIX . $userId . ':' . now()->format('Y-m-d');
        $count = app()->environment('testing')
            ? (int) Cache::get($dailyKey, 0)
            : (int) Redis::get($dailyKey);
        $limit = config('gamification.daily_visit_limit', 20);

        return $count >= $limit;
    }

    public function isImpossibleSpeed(int $userId, float $lat, float $lng): bool
    {
        $lastVisit = ArborisisVisit::where('user_id', $userId)
            ->where('visited_at', '>=', now()->subHours(2))
            ->latest('visited_at')
            ->first();

        if (! $lastVisit) {
            return false;
        }

        $geoService = app(GeoValidationService::class);
        $distance = $geoService->distance(
            (float) $lastVisit->latitude,
            (float) $lastVisit->longitude,
            $lat,
            $lng,
        );

        $timeDiffSeconds = now()->diffInSeconds($lastVisit->visited_at);

        if ($timeDiffSeconds <= 0) {
            return false;
        }

        $speedMps = $distance / $timeDiffSeconds;
        $maxSpeedMps = config('gamification.max_speed_mps', 55.56); // ~200 km/h

        return $speedMps > $maxSpeedMps;
    }

    public function getCooldownRemaining(int $userId, int $pointId): int
    {
        $redisKey = self::COOLDOWN_KEY_PREFIX . "{$userId}:{$pointId}";
        if (app()->environment('testing')) {
            return Cache::has($redisKey) ? (int) config('gamification.visit_cooldown', 3600) : 0;
        }

        $ttl = Redis::ttl($redisKey);

        return max($ttl, 0);
    }
}
