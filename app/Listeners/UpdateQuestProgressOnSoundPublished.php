<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\SoundPublished;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnSoundPublished
{
    public function handle(SoundPublished $event): void
    {
        $user = $event->sound->user;

        if (! $user) {
            return;
        }

        app(QuestService::class)->updateProgress($user, ObjectiveType::UploadSound);
    }
}
