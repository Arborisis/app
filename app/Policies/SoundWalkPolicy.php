<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SoundWalk;
use App\Models\User;

class SoundWalkPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, SoundWalk $soundWalk): bool
    {
        if ($soundWalk->isPubliclyVisible()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->id === $soundWalk->user_id || $user->isAdmin() || $user->isModerator();
    }

    public function create(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    public function update(User $user, SoundWalk $soundWalk): bool
    {
        return $user->id === $soundWalk->user_id || $user->isAdmin() || $user->isModerator();
    }

    public function delete(User $user, SoundWalk $soundWalk): bool
    {
        return $user->id === $soundWalk->user_id || $user->isAdmin() || $user->isModerator();
    }

    public function deleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function restore(User $user, SoundWalk $soundWalk): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function restoreAny(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function forceDelete(User $user, SoundWalk $soundWalk): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }

    public function moderate(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
