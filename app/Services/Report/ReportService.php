<?php

declare(strict_types=1);

namespace App\Services\Report;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Sound;
use App\Services\Discord\DiscordNotificationService;

class ReportService
{
    public function __construct(
        private readonly DiscordNotificationService $discordNotification,
    ) {}

    public function create(int $reporterId, string $reportableType, int $reportableId, string $reason, ?string $description): Report
    {
        $reportable = match ($reportableType) {
            'sound' => Sound::findOrFail($reportableId),
            'comment' => Comment::findOrFail($reportableId),
            default => throw new \InvalidArgumentException('Type de signalement invalide.'),
        };

        $report = Report::create([
            'reporter_id' => $reporterId,
            'reportable_type' => get_class($reportable),
            'reportable_id' => $reportable->id,
            'reason' => $reason,
            'description' => $description,
        ]);

        $this->discordNotification->notifyReportSubmitted($report);

        return $report;
    }
}
