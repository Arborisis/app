<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\XpEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class XpGained
{
    use Dispatchable, SerializesModels;

    public function __construct(public XpEvent $xpEvent)
    {
    }
}
