<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNearbyDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $initiator,
        public User $recipient,
        public int $distanceMeters,
        public string $interactionType,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("App.Models.User.{$this->recipient->id}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'initiator_id' => $this->initiator->id,
            'initiator_name' => $this->initiator->name,
            'distance_meters' => $this->distanceMeters,
            'interaction_type' => $this->interactionType,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.nearby';
    }
}
