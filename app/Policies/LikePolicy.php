<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Sound;
use App\Models\User;

class LikePolicy
{
    public function like(User $user, Sound $sound): bool
    {
        return $sound->isPublic() || $user->id === $sound->user_id || $user->isModerator();
    }
}
