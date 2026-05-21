<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Stats\StatsCacheService;
use Illuminate\Console\Command;

class RefreshStatsCache extends Command
{
    protected $signature = 'stats:refresh
                            {--key= : Refresh a specific stat key}
                            {--all : Refresh all stats}';

    protected $description = 'Refresh cached statistics';

    public function handle(StatsCacheService $cache): int
    {
        if ($this->option('key')) {
            $key = $this->option('key');
            $this->info("Refreshing stat: {$key}");
            $cache->refresh($key);
            $this->info("Done.");

            return self::SUCCESS;
        }

        $this->info('Refreshing all statistics...');
        $cache->refreshAll();
        $this->info('All statistics refreshed.');

        return self::SUCCESS;
    }
}
