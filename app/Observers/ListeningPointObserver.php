<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ListeningPointVersionEvent;
use App\Models\ListeningPoint;
use App\Services\Scientific\ListeningPointVersionService;

class ListeningPointObserver
{
    private const STATS_FIELDS = [
        'recordings_count',
        'species_detected_count',
        'first_recorded_at',
        'last_recorded_at',
        'dominant_tags',
        'stats_cache',
        'updated_at',
    ];

    public function created(ListeningPoint $point): void
    {
        app(ListeningPointVersionService::class)->capture(
            point: $point,
            event: ListeningPointVersionEvent::Created,
            actor: auth()->user(),
            source: 'observer',
        );
    }

    public function updated(ListeningPoint $point): void
    {
        $changed = array_keys($point->getChanges());
        $event = empty(array_diff($changed, self::STATS_FIELDS))
            ? ListeningPointVersionEvent::StatsRefreshed
            : ListeningPointVersionEvent::Updated;

        app(ListeningPointVersionService::class)->capture(
            point: $point,
            event: $event,
            actor: auth()->user(),
            source: 'observer',
        );
    }

    public function deleting(ListeningPoint $point): void
    {
        app(ListeningPointVersionService::class)->capture(
            point: $point,
            event: ListeningPointVersionEvent::Deleted,
            actor: auth()->user(),
            source: 'observer',
        );
    }
}
