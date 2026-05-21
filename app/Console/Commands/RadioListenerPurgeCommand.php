<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Radio\ListenerSessionTracker;
use Illuminate\Console\Command;

class RadioListenerPurgeCommand extends Command
{
    protected $signature = 'radio:listeners:purge {--ttl= : TTL in seconds}';

    protected $description = 'Expire stale radio listener sessions';

    public function handle(ListenerSessionTracker $tracker): int
    {
        $ttl = (int) ($this->option('ttl') ?: config('radio.listener_ttl_seconds', 120) * 2);
        $count = $tracker->purgeExpired($ttl);

        $this->info("Expired {$count} listener session(s).");

        return self::SUCCESS;
    }
}
