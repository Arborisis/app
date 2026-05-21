<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Sound;
use App\Models\ListeningPoint;
use App\Services\Scientific\OpenMeteoEnvironmentalDataService;
use Illuminate\Console\Command;

class BackfillScientificWeather extends Command
{
    protected $signature = 'scientific:backfill-weather
                            {--limit=100 : Maximum number of public sounds to enrich}
                            {--scope=sounds : Enrichment scope: sounds, points, or all}
                            {--force : Refresh existing Open-Meteo observations}';

    protected $description = 'Backfill Open-Meteo environmental observations for public scientific sounds';

    public function handle(OpenMeteoEnvironmentalDataService $weatherService): int
    {
        $limit = max(1, min((int) $this->option('limit'), 1000));
        $force = (bool) $this->option('force');
        $scope = (string) $this->option('scope');

        if (! in_array($scope, ['sounds', 'points', 'all'], true)) {
            $this->error('Invalid scope. Expected one of: sounds, points, all.');

            return self::FAILURE;
        }

        $sounds = collect();
        if ($scope === 'sounds' || $scope === 'all') {
            $soundQuery = Sound::public()
            ->with(['soundLocation', 'environmentalObservation'])
            ->whereNotNull('recorded_at')
            ->whereHas('soundLocation', fn ($q) => $q
                ->whereNotNull('public_latitude')
                ->whereNotNull('public_longitude'));

            if (! $force) {
                $soundQuery->whereDoesntHave('environmentalObservation', fn ($q) => $q->where('source', 'like', '%open-meteo%'));
            }

            $sounds = $soundQuery
                ->oldest('recorded_at')
                ->limit($limit)
                ->get();
        }

        $points = collect();
        if ($scope === 'points' || $scope === 'all') {
            $pointQuery = ListeningPoint::approved()
                ->with('environmentalObservations')
                ->whereNotNull('public_latitude')
                ->whereNotNull('public_longitude');

            if (! $force) {
                $pointQuery->whereDoesntHave('environmentalObservations', fn ($q) => $q
                    ->whereNull('sound_id')
                    ->where('source', 'like', '%open-meteo%'));
            }

            $points = $pointQuery
                ->oldest('approved_at')
                ->limit($limit)
                ->get();
        }

        if ($sounds->isEmpty() && $points->isEmpty()) {
            $this->info('No public sounds or listening points need Open-Meteo enrichment.');

            return self::SUCCESS;
        }

        $createdOrUpdated = 0;
        $skipped = 0;

        if ($points->isNotEmpty()) {
            $this->info("Enriching {$points->count()} public listening points...");
            $this->withProgressBar($points, function (ListeningPoint $point) use ($weatherService, $force, &$createdOrUpdated, &$skipped): void {
                $observation = $weatherService->enrichListeningPoint($point, force: $force);

                if ($observation && str_contains((string) $observation->source, 'open-meteo')) {
                    $createdOrUpdated++;

                    return;
                }

                $skipped++;
            });
            $this->newLine(2);
        }

        if ($sounds->isNotEmpty()) {
            $this->info("Enriching {$sounds->count()} public sounds...");
            $this->withProgressBar($sounds, function (Sound $sound) use ($weatherService, $force, &$createdOrUpdated, &$skipped): void {
                $observation = $weatherService->enrichSound($sound, $force);

                if ($observation && str_contains((string) $observation->source, 'open-meteo')) {
                    $createdOrUpdated++;

                    return;
                }

                $skipped++;
            });
            $this->newLine(2);
        }

        $this->info("Open-Meteo observations created/updated: {$createdOrUpdated}");
        $this->info("Skipped or unavailable: {$skipped}");

        return self::SUCCESS;
    }
}
