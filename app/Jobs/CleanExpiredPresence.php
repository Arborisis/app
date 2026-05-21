<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Gamification\UserLeftMap;
use App\Models\UserPresence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanExpiredPresence implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expired = UserPresence::where('expires_at', '<=', now())->get();

        foreach ($expired as $presence) {
            UserLeftMap::dispatch($presence->user_id);
        }

        UserPresence::where('expires_at', '<=', now())->delete();
    }
}
