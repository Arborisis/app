<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Enums\VisitStatus;
use App\Enums\VisitValidationReason;
use App\Events\Gamification\ArborisisPointVisited;
use App\Events\Gamification\SuspiciousVisitDetected;
use App\Http\Controllers\Controller;
use App\Models\ArborisisPoint;
use App\Models\ArborisisVisit;
use App\Services\Gamification\AntiCheatService;
use App\Services\Gamification\GeoValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArborisisVisitController extends Controller
{
    public function __construct(
        private readonly GeoValidationService $geoService,
        private readonly AntiCheatService $antiCheatService,
    ) {
    }

    public function visit(Request $request, ArborisisPoint $arborisisPoint): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'consent_given' => ['required', 'boolean'],
        ]);

        if (! $validated['consent_given']) {
            return response()->json([
                'message' => 'Le consentement de géolocalisation est requis pour effectuer un check-in.',
                'requires_consent' => true,
            ], 403);
        }

        $user = $request->user();
        $userLat = (float) $validated['latitude'];
        $userLng = (float) $validated['longitude'];
        $accuracy = isset($validated['accuracy']) ? (float) $validated['accuracy'] : null;

        // Anti-cheat validation
        $validation = $this->antiCheatService->validateVisit(
            $user,
            $arborisisPoint,
            $userLat,
            $userLng,
            $accuracy,
        );

        $status = $validation['is_valid'] ? VisitStatus::Valid : VisitStatus::Invalid;
        $validationReason = $this->determineValidationReason($validation['issues']);

        if (
            ! $validation['is_valid']
            && $validation['score'] < 30
            && in_array('impossible_speed', $validation['issues'], true)
        ) {
            $status = VisitStatus::Suspicious;
        }

        $visit = DB::transaction(function () use (
            $user,
            $arborisisPoint,
            $userLat,
            $userLng,
            $validation,
            $status,
            $validationReason,
            $accuracy,
        ) {
            $visit = ArborisisVisit::create([
                'user_id' => $user->id,
                'arborisis_point_id' => $arborisisPoint->id,
                'latitude' => round($userLat, 4), // Store approximated user position
                'longitude' => round($userLng, 4),
                'distance_from_point' => $validation['distance'],
                'visited_at' => now(),
                'device_accuracy' => $accuracy,
                'status' => $status,
                'validation_reason' => $validationReason,
                'anti_cheat_score' => $validation['score'],
                'anti_cheat_notes' => !empty($validation['issues']) ? implode(', ', $validation['issues']) : null,
            ]);

            if ($status === VisitStatus::Valid) {
                $this->antiCheatService->recordVisit($user, $arborisisPoint->id);
                ArborisisPointVisited::dispatch($visit);
            }

            if ($status === VisitStatus::Suspicious) {
                SuspiciousVisitDetected::dispatch($visit);
            }

            return $visit;
        });

        if ($status === VisitStatus::Valid) {
            return response()->json([
                'message' => 'Visite enregistrée avec succès. Tu as découvert un nouvel écho naturel.',
                'visit' => [
                    'id' => $visit->id,
                    'distance' => round($validation['distance'], 1),
                    'anti_cheat_score' => $visit->anti_cheat_score,
                ],
            ]);
        }

        if ($status === VisitStatus::Suspicious) {
            return response()->json([
                'message' => 'Cette visite a été marquée comme suspecte et sera examinée.',
                'visit' => [
                    'id' => $visit->id,
                    'reason' => $validationReason->label(),
                    'anti_cheat_score' => $visit->anti_cheat_score,
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Visite invalide : ' . $validationReason->label(),
            'reason' => $validationReason->value,
            'distance' => round($validation['distance'], 1),
            'max_radius' => config('gamification.check_in_radius', 100),
        ], 422);
    }

    public function history(Request $request): JsonResponse
    {
        $visits = ArborisisVisit::with('arborisisPoint:id,title,slug,category')
            ->where('user_id', $request->user()->id)
            ->valid()
            ->latest('visited_at')
            ->paginate(20);

        return response()->json($visits);
    }

    public function visitedPoints(Request $request): JsonResponse
    {
        $pointIds = ArborisisVisit::where('user_id', $request->user()->id)
            ->valid()
            ->distinct()
            ->pluck('arborisis_point_id');

        $points = ArborisisPoint::whereIn('id', $pointIds)
            ->public()
            ->get(['id', 'title', 'slug', 'category', 'approximate_latitude', 'approximate_longitude']);

        return response()->json($points);
    }

    private function determineValidationReason(array $issues): VisitValidationReason
    {
        if (empty($issues)) {
            return VisitValidationReason::WithinRange;
        }

        $priority = [
            'point_not_approved' => VisitValidationReason::PointNotApproved,
            'too_far' => VisitValidationReason::TooFar,
            'impossible_speed' => VisitValidationReason::ImpossibleSpeed,
            'daily_limit_reached' => VisitValidationReason::DailyLimitReached,
            'cooldown_active' => VisitValidationReason::CooldownActive,
            'poor_accuracy' => VisitValidationReason::PoorAccuracy,
        ];

        foreach ($priority as $issue => $reason) {
            if (in_array($issue, $issues, true)) {
                return $reason;
            }
        }

        return VisitValidationReason::WithinRange;
    }
}
