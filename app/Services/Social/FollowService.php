<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FollowService
{
    public function follow(User $follower, User $followed): array
    {
        if ($follower->id === $followed->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous suivre vous-même.');
        }

        return DB::transaction(function () use ($follower, $followed) {
            $existing = Follow::withTrashed()
                ->where('follower_id', $follower->id)
                ->where('followed_id', $followed->id)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $following = true;
            } else {
                Follow::create([
                    'follower_id' => $follower->id,
                    'followed_id' => $followed->id,
                ]);
                $following = true;
            }

            return [
                'following' => $following,
                'followers_count' => $followed->fresh()->followers()->count(),
            ];
        });
    }

    public function unfollow(User $follower, User $followed): array
    {
        if ($follower->id === $followed->id) {
            throw new \InvalidArgumentException('Vous ne pouvez pas vous suivre vous-même.');
        }

        return DB::transaction(function () use ($follower, $followed) {
            $existing = Follow::where('follower_id', $follower->id)
                ->where('followed_id', $followed->id)
                ->first();

            if ($existing) {
                $existing->delete();
            }

            return [
                'following' => false,
                'followers_count' => $followed->fresh()->followers()->count(),
            ];
        });
    }
}
