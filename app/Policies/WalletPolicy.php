<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }

    public function checkout(User $user): bool
    {
        return true;
    }
}
