<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatPrivateMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PrivateMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatPrivateMessage $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.conversation.'.$this->message->chat_conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'body' => $this->message->body,
                'user' => [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                ],
                'created_at' => $this->message->created_at->toIso8601String(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'PrivateMessageSent';
    }
}
