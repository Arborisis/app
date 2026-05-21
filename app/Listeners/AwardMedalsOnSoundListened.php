<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\SoundListened;
use App\Services\Gamification\MedalService;

class AwardMedalsOnSoundListened
{
    public function handle(SoundListened $event): void
    {
        app(MedalService::class)->checkAndAward($event->user, 'listen_count');
    }
}
