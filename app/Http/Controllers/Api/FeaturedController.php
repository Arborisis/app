<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sound;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class FeaturedController extends Controller
{
    public function sounds(): JsonResponse
    {
        $sounds = Sound::public()
            ->with(['user', 'category', 'soundFile'])
            ->latest()
            ->take(6)
            ->get()
            ->map(fn (Sound $sound) => [
                'id' => $sound->id,
                'slug' => $sound->slug,
                'title' => $sound->title,
                'user_name' => $sound->user->name,
                'audio_url' => $sound->audio_url,
                'cover_url' => $sound->cover_url,
                'duration' => $sound->duration,
                'play_count' => $sound->play_count,
                'like_count' => $sound->like_count,
                'category' => $sound->category?->only('id', 'name'),
            ]);

        return response()->json($sounds);
    }

    public function creators(): JsonResponse
    {
        $creators = User::whereHas('sounds', function ($query): void {
            $query->public();
        })
            ->with('profile')
            ->withCount(['sounds' => fn ($q) => $q->public()])
            ->withSum(['sounds as total_plays' => fn ($q) => $q->public()], 'play_count')
            ->orderByDesc('sounds_count')
            ->take(3)
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'slug' => $user->slug,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'location' => $user->location,
                'sounds_count' => $user->sounds_count,
                'total_plays' => $user->total_plays ?? 0,
                'featured_sound' => (function () use ($user) {
                    $popularSound = Sound::public()
                        ->with('soundFile')
                        ->where('user_id', $user->id)
                        ->orderByDesc('play_count')
                        ->first();

                    return $popularSound ? [
                        'slug' => $popularSound->slug,
                        'title' => $popularSound->title,
                        'cover_url' => $popularSound->cover_url,
                    ] : null;
                })(),
            ]);

        return response()->json($creators);
    }
}
