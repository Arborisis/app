<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearMapCache extends Command
{
    protected $signature = 'map:clear-cache';

    protected $description = 'Clear the map sounds cache';

    public function handle(): int
    {
        $keys = Cache::get('map:cache:keys', []);
        $count = 0;

        foreach ($keys as $key) {
            Cache::forget($key);
            $count++;
        }

        // Also clear using pattern if supported
        if (method_exists(Cache::store()->store(), 'flush')) {
            // Clear all map cache keys
            Cache::flush();
            $this->info('All cache cleared successfully.');
            return Command::SUCCESS;
        }

        $this->info("Cleared {$count} map cache entries.");
        return Command::SUCCESS;
    }
}
