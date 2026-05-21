<?php

declare(strict_types=1);

namespace App\Services\Social;

use App\Events\Gamification\SoundLiked;
use App\Models\Like;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LikeService
{
    public function like(User $user, Sound $sound): array
    {
        return DB::transaction(function () use ($user, $sound) {
            $existing = Like::withTrashed()
                ->where('user_id', $user->id)
                ->where('sound_id', $sound->id)
                ->first();

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                    $sound->increment('like_count');
                }
                $liked = true;
            } else {
                Like::create([
                    'user_id' => $user->id,
                    'sound_id' => $sound->id,
                ]);
                $sound->increment('like_count');
                $liked = true;

                SoundLiked::dispatch($user, $sound);
            }

            return ['liked' => $liked, 'count' => $sound->fresh()->like_count];
        });
    }

    public function unlike(User $user, Sound $sound): array
    {
        return DB::transaction(function () use ($user, $sound) {
            $existing = Like::where('user_id', $user->id)
                ->where('sound_id', $sound->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $sound->decrement('like_count');
            }

            return ['liked' => false, 'count' => $sound->fresh()->like_count];
        });
    }
}
