<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Storage\SignedUrlService;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SocialListController extends Controller
{
    public function followers(User $user): Response
    {
        $followers = $user->followers()
            ->with('profile')
            ->paginate(24);

        $followers->getCollection()->transform(function ($follower) {
            $follower->avatar_url = $this->resolveAvatarUrl($follower);
            return $follower;
        });

        return Inertia::render('Social/Followers', [
            'user' => $user->only('id', 'name', 'slug'),
            'list' => $followers,
        ]);
    }

    public function following(User $user): Response
    {
        $following = $user->following()
            ->with('profile')
            ->paginate(24);

        $following->getCollection()->transform(function ($followed) {
            $followed->avatar_url = $this->resolveAvatarUrl($followed);
            return $followed;
        });

        return Inertia::render('Social/Following', [
            'user' => $user->only('id', 'name', 'slug'),
            'list' => $following,
        ]);
    }

    public function friends(User $user): Response
    {
        $friends = $user->friends()
            ->with('profile')
            ->paginate(24);

        $friends->getCollection()->transform(function ($friend) {
            $friend->avatar_url = $this->resolveAvatarUrl($friend);
            return $friend;
        });

        return Inertia::render('Social/Friends', [
            'user' => $user->only('id', 'name', 'slug'),
            'list' => $friends,
        ]);
    }

    private function resolveAvatarUrl(User $user): ?string
    {
        if (!$user->profile?->avatar) {
            return null;
        }

        $disk = config('filesystems.default') === 'local' && empty(config('filesystems.disks.r2.key'))
            ? 'public'
            : 'r2';

        return $disk === 'r2'
            ? app(SignedUrlService::class)->url($disk, $user->profile->avatar)
            : Storage::disk($disk)->url($user->profile->avatar);
    }
}
