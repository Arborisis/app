<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ArborisisPoint;
use App\Models\User;

class ArborisisPointPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, ArborisisPoint $point): bool
    {
        if ($point->isPubliclyVisible()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->id === $point->user_id || $user->isAdmin() || $user->isModerator();
    }

    public function create(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    public function update(User $user, ArborisisPoint $point): bool
    {
        return $user->id === $point->user_id || $user->isAdmin() || $user->isModerator();
    }

    public function delete(User $user, ArborisisPoint $point): bool
    {
        return $user->id === $point->user_id || $user->isAdmin() || $user->isModerator();
    }

    public function report(User $user, ArborisisPoint $point): bool
    {
        return $user->id !== $point->user_id;
    }

    public function suggestEdit(User $user, ArborisisPoint $point): bool
    {
        return $user->id !== $point->user_id && $point->isPubliclyVisible();
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
