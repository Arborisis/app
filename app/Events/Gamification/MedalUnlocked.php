<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\UserMedal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MedalUnlocked
{
    use Dispatchable, SerializesModels;

    public function __construct(public UserMedal $userMedal)
    {
    }
}
