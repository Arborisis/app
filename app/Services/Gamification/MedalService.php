<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Events\Gamification\MedalUnlocked;
use App\Models\Medal;
use App\Models\User;
use App\Models\UserMedal;
use Illuminate\Support\Facades\DB;

class MedalService
{
    public function checkAndAward(User $user, string $conditionType, ?array $context = null): void
    {
        $medals = Medal::active()
            ->where('unlock_condition_type', $conditionType)
            ->whereDoesntHave('userMedals', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        foreach ($medals as $medal) {
            if ($this->conditionMet($user, $medal, $context)) {
                $this->award($user, $medal->id, $context['source_type'] ?? null, $context['source_id'] ?? null);
            }
        }
    }

    public function award(User $user, int $medalId, ?string $sourceType = null, ?int $sourceId = null): UserMedal
    {
        return DB::transaction(function () use ($user, $medalId, $sourceType, $sourceId) {
            $userMedal = UserMedal::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'medal_id' => $medalId,
                ],
                [
                    'unlocked_at' => now(),
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]
            );

            if ($userMedal->wasRecentlyCreated) {
                MedalUnlocked::dispatch($userMedal);
            }

            return $userMedal;
        });
    }

    private function conditionMet(User $user, Medal $medal, ?array $context): bool
    {
        $value = $medal->unlock_condition_value ?? [];

        return match ($medal->unlock_condition_type) {
            'first_visit' => $user->arborisisVisits()->valid()->exists(),
            'visit_count' => $user->arborisisVisits()->valid()->count() >= ($value['count'] ?? 10),
            'visit_category_count' => $this->hasVisitedCategoryCount($user, $value['category'] ?? '', $value['count'] ?? 5),
            'create_point_count' => $user->arborisisPoints()->where('moderation_status', 'approved')->count() >= ($value['count'] ?? 10),
            'report_valid_count' => $user->pointReports()->where('status', 'resolved')->count() >= ($value['count'] ?? 5),
            'listen_count' => $user->soundListens()->count() >= ($value['count'] ?? 10),
            default => false,
        };
    }

    private function hasVisitedCategoryCount(User $user, string $category, int $count): bool
    {
        return $user->arborisisVisits()
            ->valid()
            ->whereHas('arborisisPoint', function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->count() >= $count;
    }
}
