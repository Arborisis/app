<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\SoundIdeas;

use App\Enums\SoundIdeaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SoundIdeas\UpdateSoundIdeaRequest;
use App\Models\DailySoundIdea;
use App\Models\UserSoundIdeaProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailySoundIdeaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $ideas = DailySoundIdea::today()
            ->orderBy('id')
            ->get()
            ->map(fn (DailySoundIdea $idea) => [
                'id' => $idea->id,
                'title' => $idea->title,
                'description' => $idea->description,
                'difficulty' => $idea->difficulty,
                'tags' => $idea->tags ?? [],
                'season_context' => $idea->season_context,
                'weather_context' => $idea->weather_context,
                'time_of_day' => $idea->time_of_day,
                'theme' => $idea->theme,
            ])
            ->toArray();

        if (empty($ideas)) {
            return response()->json([
                'ideas' => [],
                'progress' => [],
                'theme' => null,
                'completed_count' => 0,
                'total_count' => 0,
            ]);
        }

        $progress = UserSoundIdeaProgress::where('user_id', $user->id)
            ->whereIn('daily_sound_idea_id', array_column($ideas, 'id'))
            ->get()
            ->keyBy('daily_sound_idea_id')
            ->map(fn (UserSoundIdeaProgress $p) => [
                'status' => $p->status->value,
                'completed_at' => $p->completed_at?->toISOString(),
                'dismissed_at' => $p->dismissed_at?->toISOString(),
            ])
            ->toArray();

        $completedCount = count(array_filter($progress, fn ($p) => $p['status'] === SoundIdeaStatus::Completed->value));

        return response()->json([
            'ideas' => $ideas,
            'progress' => $progress,
            'theme' => $ideas[0]['theme'] ?? null,
            'completed_count' => $completedCount,
            'total_count' => count($ideas),
        ]);
    }

    public function toggle(Request $request, DailySoundIdea $idea): JsonResponse
    {
        $user = $request->user();

        $progress = UserSoundIdeaProgress::firstOrNew([
            'user_id' => $user->id,
            'daily_sound_idea_id' => $idea->id,
        ]);

        if ($progress->status === SoundIdeaStatus::Completed) {
            $progress->status = SoundIdeaStatus::Pending;
            $progress->completed_at = null;
        } else {
            $progress->status = SoundIdeaStatus::Completed;
            $progress->completed_at = now();
            $progress->dismissed_at = null;
        }

        $progress->save();

        return response()->json([
            'id' => $idea->id,
            'status' => $progress->status->value,
            'completed_at' => $progress->completed_at?->toISOString(),
            'completed_count' => $this->getCompletedCount($user),
            'total_count' => DailySoundIdea::today()->count(),
        ]);
    }

    public function dismiss(Request $request, DailySoundIdea $idea): JsonResponse
    {
        $user = $request->user();

        $progress = UserSoundIdeaProgress::firstOrNew([
            'user_id' => $user->id,
            'daily_sound_idea_id' => $idea->id,
        ]);

        $progress->status = SoundIdeaStatus::Dismissed;
        $progress->dismissed_at = now();
        $progress->completed_at = null;
        $progress->save();

        return response()->json([
            'id' => $idea->id,
            'status' => $progress->status->value,
            'dismissed_at' => $progress->dismissed_at?->toISOString(),
            'completed_count' => $this->getCompletedCount($user),
            'total_count' => DailySoundIdea::today()->count(),
        ]);
    }

    private function getCompletedCount(\App\Models\User $user): int
    {
        return UserSoundIdeaProgress::where('user_id', $user->id)
            ->where('status', SoundIdeaStatus::Completed)
            ->whereHas('dailySoundIdea', fn ($q) => $q->today())
            ->count();
    }
}
