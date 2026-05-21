<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\ModerationStatus;
use App\Events\Gamification\ArborisisPointApproved;
use App\Events\Gamification\ArborisisPointRejected;
use App\Models\ArborisisPoint;
use App\Models\PointReport;
use App\Models\PointSuggestion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ArborisisPointModerationService
{
    public function approve(ArborisisPoint $point, User $admin, ?string $notes = null): ArborisisPoint
    {
        return DB::transaction(function () use ($point, $admin, $notes) {
            $coordinates = ArborisisPoint::publicCoordinates(
                (float) $point->latitude,
                (float) $point->longitude,
                $point->nature_sensitivity_level,
            );

            $point->update([
                'moderation_status' => ModerationStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $admin->id,
                'approximate_latitude' => $coordinates['approximate_latitude'],
                'approximate_longitude' => $coordinates['approximate_longitude'],
            ]);

            ArborisisPointApproved::dispatch($point, $admin);

            return $point->fresh();
        });
    }

    public function reject(ArborisisPoint $point, User $admin, ?string $reason = null): ArborisisPoint
    {
        return DB::transaction(function () use ($point, $admin, $reason) {
            $point->update([
                'moderation_status' => ModerationStatus::Rejected,
            ]);

            ArborisisPointRejected::dispatch($point, $admin, $reason);

            return $point->fresh();
        });
    }

    public function hide(ArborisisPoint $point, User $admin, ?string $reason = null): ArborisisPoint
    {
        return DB::transaction(function () use ($point, $admin, $reason) {
            $point->update([
                'moderation_status' => ModerationStatus::Hidden,
            ]);

            return $point->fresh();
        });
    }

    public function archive(ArborisisPoint $point, User $admin): ArborisisPoint
    {
        return DB::transaction(function () use ($point, $admin) {
            $point->update([
                'moderation_status' => ModerationStatus::Archived,
            ]);

            return $point->fresh();
        });
    }

    public function reviewReport(PointReport $report, User $admin, string $status, ?string $notes = null): PointReport
    {
        return DB::transaction(function () use ($report, $admin, $status, $notes) {
            $report->update([
                'status' => $status,
                'admin_notes' => $notes,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($status === 'resolved') {
                $report->arborisisPoint()->update([
                    'moderation_status' => ModerationStatus::Hidden,
                ]);
            }

            return $report->fresh();
        });
    }

    public function reviewSuggestion(PointSuggestion $suggestion, User $admin, bool $accept): PointSuggestion
    {
        return DB::transaction(function () use ($suggestion, $admin, $accept) {
            $suggestion->update([
                'status' => $accept ? 'accepted' : 'rejected',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            if ($accept) {
                $suggestion->arborisisPoint()->update([
                    $suggestion->field => $suggestion->proposed_value,
                ]);
            }

            return $suggestion->fresh();
        });
    }
}
