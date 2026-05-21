<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\SoundListened;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnSoundListened
{
    public function handle(SoundListened $event): void
    {
        app(QuestService::class)->updateProgress($event->user, ObjectiveType::ListenSound);
    }
}
