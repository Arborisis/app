<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\SoundLiked;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnSoundLiked
{
    public function handle(SoundLiked $event): void
    {
        $owner = $event->sound->user;

        if (! $owner) {
            return;
        }

        app(QuestService::class)->updateProgress($owner, ObjectiveType::ReceiveLike);
    }
}
