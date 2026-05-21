<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactTicket;
use App\Models\User;

class ContactTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, ContactTicket $contactTicket): bool
    {
        return $user->isModerator();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ContactTicket $contactTicket): bool
    {
        return $user->isModerator();
    }

    public function delete(User $user, ContactTicket $contactTicket): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, ContactTicket $contactTicket): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, ContactTicket $contactTicket): bool
    {
        return $user->isAdmin();
    }
}
