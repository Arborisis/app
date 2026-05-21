<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Enums\ModerationStatus;
use App\Events\Gamification\SoundWalkApproved;
use App\Http\Controllers\Controller;
use App\Models\SoundWalk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSoundWalkController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $this->authorize('moderate', SoundWalk::class);

        $pending = SoundWalk::with('user:id,name,slug')
            ->pending()
            ->latest()
            ->paginate(20);

        return response()->json($pending);
    }

    public function approve(Request $request, SoundWalk $soundWalk): JsonResponse
    {
        $this->authorize('moderate', $soundWalk);

        $soundWalk->update([
            'moderation_status' => ModerationStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
        ]);

        SoundWalkApproved::dispatch($soundWalk, $request->user());

        return response()->json([
            'message' => 'Balade approuvée.',
            'sound_walk' => [
                'id' => $soundWalk->id,
                'slug' => $soundWalk->slug,
                'title' => $soundWalk->title,
                'moderation_status' => $soundWalk->moderation_status->value,
            ],
        ]);
    }

    public function reject(Request $request, SoundWalk $soundWalk): JsonResponse
    {
        $this->authorize('moderate', $soundWalk);

        $soundWalk->update([
            'moderation_status' => ModerationStatus::Rejected,
        ]);

        return response()->json([
            'message' => 'Balade rejetée.',
            'sound_walk' => [
                'id' => $soundWalk->id,
                'slug' => $soundWalk->slug,
                'title' => $soundWalk->title,
                'moderation_status' => $soundWalk->moderation_status->value,
            ],
        ]);
    }

    public function hide(Request $request, SoundWalk $soundWalk): JsonResponse
    {
        $this->authorize('moderate', $soundWalk);

        $soundWalk->update([
            'moderation_status' => ModerationStatus::Hidden,
        ]);

        return response()->json([
            'message' => 'Balade masquée.',
            'sound_walk' => [
                'id' => $soundWalk->id,
                'slug' => $soundWalk->slug,
                'title' => $soundWalk->title,
                'moderation_status' => $soundWalk->moderation_status->value,
            ],
        ]);
    }
}
