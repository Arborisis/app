<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\ArborisisPointApproved;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnPointApproved
{
    public function handle(ArborisisPointApproved $event): void
    {
        app(QuestService::class)->updateProgress($event->point->user, ObjectiveType::CreatePoint);
    }
}
