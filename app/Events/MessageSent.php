<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.room.'.$this->message->chat_room_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'type' => $this->message->type->value,
                'user' => [
                    'id' => $this->message->user?->id,
                    'name' => $this->message->user?->name,
                    'is_moderator' => $this->message->user?->isModerator() ?? false,
                ],
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }
}
