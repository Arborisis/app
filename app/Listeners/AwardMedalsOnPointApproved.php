<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ArborisisPointApproved;
use App\Services\Gamification\MedalService;

class AwardMedalsOnPointApproved
{
    public function handle(ArborisisPointApproved $event): void
    {
        app(MedalService::class)->checkAndAward($event->point->user, 'create_point_count');
    }
}
