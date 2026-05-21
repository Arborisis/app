<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $posts = BlogPost::latestPublished()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Blog/Index', [
            'posts' => $posts,
        ]);
    }

    public function show(string $slug): Response
    {
        $post = BlogPost::where('slug', $slug)
            ->published()
            ->firstOrFail();

        $relatedSounds = [];
        if (! empty($post->related_sounds)) {
            $soundIds = array_column($post->related_sounds, 'sound_id');
            $sounds = Sound::whereIn('id', $soundIds)
                ->with('category')
                ->get()
                ->keyBy('id');

            foreach ($post->related_sounds as $related) {
                $sound = $sounds[$related['sound_id']] ?? null;
                if ($sound) {
                    $relatedSounds[] = [
                        ...$related,
                        'sound' => $sound,
                    ];
                }
            }
        }

        $relatedCreators = [];
        if (! empty($post->related_creators)) {
            $creatorIds = array_column($post->related_creators, 'user_id');
            $creators = User::whereIn('id', $creatorIds)
                ->with('profile')
                ->get()
                ->keyBy('id');

            foreach ($post->related_creators as $related) {
                $creator = $creators[$related['user_id']] ?? null;
                if ($creator) {
                    $relatedCreators[] = [
                        ...$related,
                        'creator' => $creator,
                    ];
                }
            }
        }

        $previousPost = BlogPost::latestPublished()
            ->where('published_at', '<', $post->published_at)
            ->first();

        $nextPost = BlogPost::latestPublished()
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at')
            ->first();

        return Inertia::render('Blog/Show', [
            'post' => $post,
            'relatedSounds' => $relatedSounds,
            'relatedCreators' => $relatedCreators,
            'previousPost' => $previousPost ? ['slug' => $previousPost->slug, 'title' => $previousPost->title] : null,
            'nextPost' => $nextPost ? ['slug' => $nextPost->slug, 'title' => $nextPost->title] : null,
        ]);
    }
}
