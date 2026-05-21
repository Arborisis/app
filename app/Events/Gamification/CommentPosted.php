<?php

declare(strict_types=1);

namespace App\Events\Gamification;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPosted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $user,
        public Comment $comment,
    ) {}
}
