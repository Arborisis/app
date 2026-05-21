<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\User;

class ChatMessagePolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, ChatMessage $message): bool
    {
        if ($user->isModerator()) {
            return true;
        }

        return $message->user_id === $user->id;
    }
}
