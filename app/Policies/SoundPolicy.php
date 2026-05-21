<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sound;
use App\Models\User;

class SoundPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(?User $user, Sound $sound): bool
    {
        if ($sound->isPublic()) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->id === $sound->user_id || $user->isModerator()) {
            return true;
        }

        return $sound->visibility->value === 'followers'
            && $user->isFollowing($sound->user);
    }

    public function create(User $user): bool
    {
        return $user->role->canUpload();
    }

    public function update(User $user, Sound $sound): bool
    {
        return $user->id === $sound->user_id || $user->isModerator();
    }

    public function delete(User $user, Sound $sound): bool
    {
        return $user->id === $sound->user_id || $user->isModerator();
    }

    public function like(User $user, Sound $sound): bool
    {
        return $sound->isPublic() || $user->id === $sound->user_id || $user->isModerator();
    }

    public function unlike(User $user, Sound $sound): bool
    {
        return $sound->isPublic() || $user->id === $sound->user_id || $user->isModerator();
    }
}
