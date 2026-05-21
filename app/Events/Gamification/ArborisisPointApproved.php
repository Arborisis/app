<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\ArborisisPoint;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ArborisisPointApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ArborisisPoint $point,
        public User $admin,
    ) {
    }
}
