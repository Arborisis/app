<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\Sound;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SoundLiked
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $liker,
        public Sound $sound,
    ) {}
}
