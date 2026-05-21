<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\QuestProgress;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuestCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public QuestProgress $progress)
    {
    }
}
