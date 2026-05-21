<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatRoom;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserBanned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatRoom $room,
        public User $bannedUser,
        public User $admin
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.room.'.$this->room->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => 'system-'.time(),
                'body' => "{$this->bannedUser->name} a été exclu du salon par {$this->admin->name}.",
                'type' => 'system',
                'user' => null,
                'created_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'UserBanned';
    }
}
