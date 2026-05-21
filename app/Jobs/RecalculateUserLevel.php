<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\Gamification\XpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateUserLevel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $userId)
    {
    }

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        app(XpService::class)->recalculateLevel($user);
    }
}
