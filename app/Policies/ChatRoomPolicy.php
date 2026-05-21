<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChatRoom;
use App\Models\User;

class ChatRoomPolicy
{
    public function view(User $user, ChatRoom $room): bool
    {
        if ($room->type->value === 'admin_only') {
            return $user->isModerator();
        }

        return true;
    }

    public function join(User $user, ChatRoom $room): bool
    {
        if ($room->isBanned($user)) {
            return false;
        }

        if ($room->type->value === 'admin_only') {
            return $user->isModerator();
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $user->isModerator();
    }

    public function delete(User $user, ChatRoom $room): bool
    {
        return $user->isAdmin() || $room->created_by === $user->id;
    }

    public function moderate(User $user, ChatRoom $room): bool
    {
        return $user->isModerator();
    }
}
