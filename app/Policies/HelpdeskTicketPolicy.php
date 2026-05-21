<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\HelpdeskTicket;
use App\Models\User;

class HelpdeskTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HelpdeskTicket $ticket): bool
    {
        return $ticket->user_id === $user->id || $this->isAgent($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function reply(User $user, HelpdeskTicket $ticket): bool
    {
        if ($ticket->user_id === $user->id && $ticket->isOpen()) {
            return true;
        }

        return $this->isAgent($user);
    }

    public function update(User $user, HelpdeskTicket $ticket): bool
    {
        return $this->isAgent($user);
    }

    public function assign(User $user, HelpdeskTicket $ticket): bool
    {
        return $this->isAgent($user);
    }

    public function validateIa(User $user): bool
    {
        return $this->isAgent($user);
    }

    public function delete(User $user, HelpdeskTicket $ticket): bool
    {
        return $this->isAgent($user);
    }

    private function isAgent(User $user): bool
    {
        return $user->isAdmin() || $user->isModerator();
    }
}
