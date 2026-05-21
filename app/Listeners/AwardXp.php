<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\Gamification\ArborisisPointVisited;
use App\Services\Gamification\XpService;

class AwardXp
{
    public function handle(ArborisisPointVisited $event): void
    {
        $visit = $event->visit;

        if (! $visit->isValid()) {
            return;
        }

        $xpAmount = config('gamification.xp.visit_validated', 10);

        app(XpService::class)->award(
            $visit->user,
            $xpAmount,
            'visit',
            $visit->id,
            'Visite validée d\'un point Arborisis'
        );
    }
}
