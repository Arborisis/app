<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\ArborisisPointSubmitted;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnPointSubmitted
{
    public function handle(ArborisisPointSubmitted $event): void
    {
        app(QuestService::class)->updateProgress($event->point->user, ObjectiveType::CreatePoint);
    }
}
