<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function follow(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }

    public function unfollow(User $user, User $model): bool
    {
        return $user->id !== $model->id;
    }
}
