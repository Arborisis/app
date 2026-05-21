<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\OpenSearch\IndexSoundInOpenSearch;
use App\Models\Sound;
use App\Services\OpenSearch\OpenSearchIndexService;
use Illuminate\Console\Command;

class OpenSearchReindex extends Command
{
    protected $signature = 'search:reindex
                            {--sounds : Reindex all sounds}
                            {--listening-points : Reindex all listening points}';
    protected $description = 'Reindex all data into OpenSearch';

    public function handle(OpenSearchIndexService $service): int
    {
        if (! $service->isAvailable()) {
            $this->error('OpenSearch is not available.');
            return self::FAILURE;
        }

        $service->createIndices();

        if ($this->option('sounds') || (! $this->option('sounds') && ! $this->option('listening-points'))) {
            $this->reindexSounds();
        }

        if ($this->option('listening-points') || (! $this->option('sounds') && ! $this->option('listening-points'))) {
            $this->reindexListeningPoints($service);
        }

        return self::SUCCESS;
    }

    private function reindexSounds(): void
    {
        $count = Sound::public()->count();
        $this->info("Reindexing {$count} sounds...");

        $progress = $this->output->createProgressBar($count);

        Sound::public()->chunkById(100, function ($sounds) use ($progress) {
            foreach ($sounds as $sound) {
                IndexSoundInOpenSearch::dispatch($sound->id)->onQueue('search');
                $progress->advance();
            }
        });

        $progress->finish();
        $this->newLine();
        $this->info('Sounds queued for reindexing.');
    }

    private function reindexListeningPoints(OpenSearchIndexService $service): void
    {
        $count = \App\Models\ListeningPoint::approved()->count();
        $this->info("Reindexing {$count} listening points...");

        $progress = $this->output->createProgressBar($count);

        \App\Models\ListeningPoint::approved()->chunkById(100, function ($points) use ($progress, $service) {
            foreach ($points as $point) {
                $service->indexListeningPoint($point);
                $progress->advance();
            }
        });

        $progress->finish();
        $this->newLine();
        $this->info('Listening points reindexed.');
    }
}
