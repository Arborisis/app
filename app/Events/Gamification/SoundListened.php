<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\Sound;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoundListened
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public Sound $sound,
        public ?int $listenedSeconds = null,
    ) {}
}
