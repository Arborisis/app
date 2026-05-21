<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLeftMap implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $userId)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('presence.map'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'left_at' => now()->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'presence.left';
    }
}
