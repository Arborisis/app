<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Events\Gamification\AchievementUnlocked;
use App\Models\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    public function checkAndUnlock(User $user, string $conditionType, ?array $context = null): void
    {
        $achievements = Achievement::active()
            ->where('condition_type', $conditionType)
            ->whereDoesntHave('userAchievements', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        foreach ($achievements as $achievement) {
            if ($this->conditionMet($user, $achievement, $context)) {
                $this->unlock($user, $achievement->id);
            }
        }
    }

    public function unlock(User $user, int $achievementId): UserAchievement
    {
        return DB::transaction(function () use ($user, $achievementId) {
            $userAchievement = UserAchievement::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'achievement_id' => $achievementId,
                ],
                [
                    'unlocked_at' => now(),
                    'progress_snapshot' => [],
                ]
            );

            if ($userAchievement->wasRecentlyCreated) {
                $achievement = Achievement::find($achievementId);
                AchievementUnlocked::dispatch($userAchievement);

                if ($achievement && $achievement->points > 0) {
                    app(XpService::class)->award($user, $achievement->points, 'achievement_unlocked', $achievementId);
                }
            }

            return $userAchievement;
        });
    }

    private function conditionMet(User $user, Achievement $achievement, ?array $context): bool
    {
        $payload = $achievement->condition_payload ?? [];

        return match ($achievement->condition_type) {
            'first_visit' => $user->arborisisVisits()->valid()->exists(),
            'first_sound' => $user->sounds()->exists(),
            'first_point_created' => $user->arborisisPoints()->exists(),
            'points_accepted' => $user->arborisisPoints()->where('moderation_status', 'approved')->exists(),
            'login_streak' => ($context['streak'] ?? 0) >= ($payload['days'] ?? 7),
            'visit_count' => $user->arborisisVisits()->valid()->count() >= ($payload['count'] ?? 10),
            'visit_category' => $this->hasVisitedCategory($user, $payload['category'] ?? ''),
            'listen_duration' => ($context['minutes'] ?? 0) >= ($payload['minutes'] ?? 30),
            'complete_profile' => $this->isProfileComplete($user, $payload),
            default => false,
        };
    }

    private function hasVisitedCategory(User $user, string $category): bool
    {
        return $user->arborisisVisits()
            ->valid()
            ->whereHas('arborisisPoint', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->exists();
    }

    private function isProfileComplete(User $user, array $payload): bool
    {
        if (isset($payload['field'])) {
            $field = $payload['field'];
            return ! empty($user->{$field});
        }

        $profile = $user->profile;

        if (! $profile) {
            return false;
        }

        return ! empty($profile->avatar)
            || ! empty($profile->bio)
            || ! empty($profile->location)
            || ! empty($profile->website);
    }
}
