<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class FollowPolicy
{
    public function follow(User $user, User $target): bool
    {
        return $user->id !== $target->id;
    }

    public function unfollow(User $user, User $target): bool
    {
        return $user->id !== $target->id;
    }
}
