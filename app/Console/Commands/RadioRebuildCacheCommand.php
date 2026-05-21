<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Radio\RadioAudioCacheService;
use App\Services\Radio\RadioPlaylistExportService;
use Illuminate\Console\Command;

class RadioRebuildCacheCommand extends Command
{
    protected $signature = 'radio:rebuild-cache
                            {--type= : Comma-separated types: sounds,jingles,podcasts,dj}
                            {--dry-run : List assets without writing}';

    protected $description = 'Rebuild the local radio audio cache for all playable assets';

    public function handle(RadioAudioCacheService $cache): int
    {
        $typeOption = $this->option('type');
        $dryRun = $this->option('dry-run');
        $types = $typeOption ? explode(',', $typeOption) : ['sounds', 'jingles', 'podcasts', 'dj'];

        if ($dryRun) {
            $this->info('[DRY RUN] No files will be written.');
        }

        foreach ($types as $type) {
            $this->info("Processing type: {$type}");

            if ($dryRun) {
                $this->dryRunForType($type);
                continue;
            }

            match ($type) {
                'sounds' => $this->warmSounds($cache),
                'jingles' => $this->warmJingles($cache),
                'podcasts' => $this->warmPodcasts($cache),
                'dj' => $this->warmDj($cache),
                default => $this->warn("Unknown type: {$type}"),
            };
        }

        if (! $dryRun) {
            $cache->cleanup();
            $this->info('Cache cleanup completed.');

            $this->regeneratePlaylistFile();
        }

        $this->info('Rebuild finished.');

        return self::SUCCESS;
    }

    private function warmSounds(RadioAudioCacheService $cache): void
    {
        $query = \App\Models\Sound::public()->with('soundFile');
        $bar = $this->output->createProgressBar($query->count());

        foreach ($query->cursor() as $sound) {
            try {
                $cache->warmSound($sound);
            } catch (\Throwable $e) {
                $this->warn("Failed to warm sound #{$sound->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function warmJingles(RadioAudioCacheService $cache): void
    {
        $query = \App\Models\RadioJingle::query()->where('is_active', true);
        $bar = $this->output->createProgressBar($query->count());

        foreach ($query->cursor() as $jingle) {
            try {
                $cache->warmJingle($jingle);
            } catch (\Throwable $e) {
                $this->warn("Failed to warm jingle #{$jingle->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function warmPodcasts(RadioAudioCacheService $cache): void
    {
        $query = \App\Models\RadioPodcast::query()->published();
        $bar = $this->output->createProgressBar($query->count());

        foreach ($query->cursor() as $podcast) {
            try {
                $cache->warmPodcast($podcast);
            } catch (\Throwable $e) {
                $this->warn("Failed to warm podcast #{$podcast->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function warmDj(RadioAudioCacheService $cache): void
    {
        $query = \App\Models\RadioDjAnnouncement::query();
        $bar = $this->output->createProgressBar($query->count());

        foreach ($query->cursor() as $ann) {
            try {
                $cache->warmDjAnnouncement($ann);
            } catch (\Throwable $e) {
                $this->warn("Failed to warm DJ announcement #{$ann->id}: {$e->getMessage()}");
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function dryRunForType(string $type): void
    {
        $ids = match ($type) {
            'sounds' => \App\Models\Sound::public()
                ->whereHas('soundFile', fn ($q) => $q->whereNotNull('path'))
                ->pluck('id'),
            'jingles' => \App\Models\RadioJingle::query()->where('is_active', true)->pluck('id'),
            'podcasts' => \App\Models\RadioPodcast::query()->published()->pluck('id'),
            'dj' => \App\Models\RadioDjAnnouncement::query()->pluck('id'),
            default => collect(),
        };

        $this->info("Would process {$ids->count()} {$type}");
    }

    private function regeneratePlaylistFile(): void
    {
        $export = app(RadioPlaylistExportService::class);
        $liqPath = storage_path('app/radio-cache/playlist.liq');
        file_put_contents($liqPath, $export->liq(), LOCK_EX);
        $this->info("Playlist file regenerated: {$liqPath}");
    }
}
