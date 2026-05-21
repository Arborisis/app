<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Controller;
use App\Models\ArborisisPoint;
use App\Models\PointReport;
use App\Models\PointSuggestion;
use App\Services\Gamification\ArborisisPointModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminArborisisPointController extends Controller
{
    public function __construct(
        private readonly ArborisisPointModerationService $moderationService,
    ) {
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $points = ArborisisPoint::with('user:id,name,email')
            ->pending()
            ->latest()
            ->paginate(20);

        return response()->json($points);
    }

    public function approve(Request $request, ArborisisPoint $arborisisPoint): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $point = $this->moderationService->approve(
            $arborisisPoint,
            $request->user(),
            $request->input('notes')
        );

        return response()->json([
            'message' => 'Point approuvé et publié.',
            'point' => [
                'id' => $point->id,
                'title' => $point->title,
                'moderation_status' => $point->moderation_status->label(),
            ],
        ]);
    }

    public function reject(Request $request, ArborisisPoint $arborisisPoint): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $point = $this->moderationService->reject(
            $arborisisPoint,
            $request->user(),
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Point rejeté.',
            'point' => [
                'id' => $point->id,
                'title' => $point->title,
                'moderation_status' => $point->moderation_status->label(),
            ],
        ]);
    }

    public function hide(Request $request, ArborisisPoint $arborisisPoint): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $point = $this->moderationService->hide(
            $arborisisPoint,
            $request->user(),
            $request->input('reason')
        );

        return response()->json([
            'message' => 'Point masqué.',
            'point' => [
                'id' => $point->id,
                'title' => $point->title,
                'moderation_status' => $point->moderation_status->label(),
            ],
        ]);
    }

    public function reports(Request $request): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $reports = PointReport::with(['user:id,name', 'arborisisPoint:id,title,slug'])
            ->pending()
            ->latest()
            ->paginate(20);

        return response()->json($reports);
    }

    public function reviewReport(Request $request, PointReport $report): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $validated = $request->validate([
            'status' => ['required', 'in:resolved,dismissed'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->moderationService->reviewReport(
            $report,
            $request->user(),
            $validated['status'],
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Signalement traité.',
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $suggestions = PointSuggestion::with(['user:id,name', 'arborisisPoint:id,title,slug'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json($suggestions);
    }

    public function reviewSuggestion(Request $request, PointSuggestion $suggestion): JsonResponse
    {
        $this->authorize('moderate', ArborisisPoint::class);

        $validated = $request->validate([
            'accept' => ['required', 'boolean'],
        ]);

        $this->moderationService->reviewSuggestion(
            $suggestion,
            $request->user(),
            $validated['accept']
        );

        return response()->json([
            'message' => $validated['accept'] ? 'Suggestion acceptée.' : 'Suggestion rejetée.',
        ]);
    }
}
