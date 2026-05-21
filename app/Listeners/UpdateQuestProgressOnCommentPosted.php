<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\ObjectiveType;
use App\Events\Gamification\CommentPosted;
use App\Services\Gamification\QuestService;

class UpdateQuestProgressOnCommentPosted
{
    public function handle(CommentPosted $event): void
    {
        app(QuestService::class)->updateProgress($event->user, ObjectiveType::Comment);
    }
}
