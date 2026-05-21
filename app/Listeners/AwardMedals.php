<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ArborisisPointVisited;
use App\Services\Gamification\MedalService;

class AwardMedals
{
    public function handle(ArborisisPointVisited $event): void
    {
        $user = $event->visit->user;

        app(MedalService::class)->checkAndAward($user, 'first_visit');
        app(MedalService::class)->checkAndAward($user, 'visit_count');
        app(MedalService::class)->checkAndAward($user, 'visit_category_count');
    }
}
