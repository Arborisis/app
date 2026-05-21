<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\ObjectiveType;
use App\Enums\QuestStatus;
use App\Events\Gamification\QuestCompleted;
use App\Models\Quest;
use App\Models\QuestProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuestService
{
    public function startQuest(User $user, Quest $quest): QuestProgress
    {
        return DB::transaction(function () use ($user, $quest) {
            $progress = QuestProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'quest_id' => $quest->id,
                ],
                [
                    'target_progress' => $quest->objective_target,
                    'status' => QuestStatus::InProgress,
                    'started_at' => now(),
                ]
            );

            return $progress;
        });
    }

    public function updateProgress(User $user, ObjectiveType $objectiveType, int $amount = 1, ?array $payload = null): void
    {
        // Auto-start active quests of this type that the user hasn't started yet
        $questsToStart = Quest::active()
            ->where('objective_type', $objectiveType)
            ->whereDoesntHave('progress', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        foreach ($questsToStart as $quest) {
            $this->startQuest($user, $quest);
        }

        $progresses = QuestProgress::with('quest')
            ->where('user_id', $user->id)
            ->where('status', QuestStatus::InProgress)
            ->whereHas('quest', function ($q) use ($objectiveType) {
                $q->where('objective_type', $objectiveType)
                    ->where('is_active', true)
                    ->where(function ($sq) {
                        $sq->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                    });
            })
            ->get();

        foreach ($progresses as $progress) {
            $quest = $progress->quest;

            // Check payload conditions if any
            if ($payload && $quest->objective_payload) {
                if (! $this->payloadMatches($quest->objective_payload, $payload)) {
                    continue;
                }
            }

            $newProgress = min($progress->current_progress + $amount, $quest->objective_target);

            $progress->update([
                'current_progress' => $newProgress,
            ]);

            if ($newProgress >= $quest->objective_target) {
                $this->completeQuest($progress);
            }
        }
    }

    public function completeQuest(QuestProgress $progress): void
    {
        if ($progress->isCompleted()) {
            return;
        }

        $progress->update([
            'status' => QuestStatus::Completed,
            'completed_at' => now(),
        ]);

        QuestCompleted::dispatch($progress);
    }

    public function claimReward(User $user, QuestProgress $progress): array
    {
        if ($progress->status !== QuestStatus::Completed) {
            throw new \RuntimeException('La quête n\'est pas terminée.');
        }

        return DB::transaction(function () use ($user, $progress) {
            $progress->update([
                'status' => QuestStatus::Claimed,
                'claimed_at' => now(),
            ]);

            $quest = $progress->quest;
            $rewards = [];

            if ($quest->reward_xp > 0) {
                app(XpService::class)->award($user, $quest->reward_xp, 'quest_completed', $quest->id);
                $rewards[] = ['type' => 'xp', 'amount' => $quest->reward_xp];
            }

            if ($quest->reward_medal_id) {
                app(MedalService::class)->award($user, $quest->reward_medal_id, 'quest', $quest->id);
                $rewards[] = ['type' => 'medal', 'medal_id' => $quest->reward_medal_id];
            }

            if ($quest->reward_achievement_id) {
                app(AchievementService::class)->unlock($user, $quest->reward_achievement_id);
                $rewards[] = ['type' => 'achievement', 'achievement_id' => $quest->reward_achievement_id];
            }

            return $rewards;
        });
    }

    private function payloadMatches(array $questPayload, array $eventPayload): bool
    {
        foreach ($questPayload as $key => $value) {
            if (! isset($eventPayload[$key]) || $eventPayload[$key] !== $value) {
                return false;
            }
        }

        return true;
    }
}
