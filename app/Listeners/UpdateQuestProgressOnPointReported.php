<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\PointReported;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnPointReported
{
    public function handle(PointReported $event): void
    {
        app(QuestService::class)->updateProgress($event->user, ObjectiveType::ReportValidIssue);
    }
}
