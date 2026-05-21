<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\UserPresence;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresenceUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public UserPresence $presence)
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
            'user_id' => $this->presence->user_id,
            'latitude' => (float) $this->presence->approximate_latitude,
            'longitude' => (float) $this->presence->approximate_longitude,
            'last_seen_at' => $this->presence->last_seen_at->toIso8601String(),
            'visibility_mode' => $this->presence->visibility_mode->value,
        ];
    }

    public function broadcastAs(): string
    {
        return 'presence.updated';
    }
}
