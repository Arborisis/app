<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Events\Gamification\CommentPosted;
use App\Models\Comment;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CommentService
{
    public function create(User $user, Sound $sound, array $data): Comment
    {
        return DB::transaction(function () use ($user, $sound, $data) {
            $comment = Comment::create([
                'user_id' => $user->id,
                'sound_id' => $sound->id,
                'parent_id' => $data['parent_id'] ?? null,
                'body' => $data['body'],
            ]);

            $sound->increment('comment_count');

            CommentPosted::dispatch($user, $comment);

            return $comment;
        });
    }

    public function delete(Comment $comment): void
    {
        DB::transaction(function () use ($comment) {
            $sound = $comment->sound;
            $comment->delete();
            $sound->decrement('comment_count');
        });
    }
}
