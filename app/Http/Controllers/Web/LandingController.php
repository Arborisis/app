<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Sound;
use App\Models\SoundLocation;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    private const CACHE_TTL = 300; // 5 minutes

    public function index(): Response
    {
        $stats = Cache::remember('landing:stats', self::CACHE_TTL, function (): array {
            return [
                'sounds' => Sound::public()->count(),
                'creators' => User::whereHas('sounds', function ($query): void {
                    $query->public();
                })->count(),
                'countries' => SoundLocation::whereHas('sound', function ($query): void {
                    $query->public();
                })
                    ->whereNotNull('location_name')
                    ->distinct()
                    ->count('location_name'),
            ];
        });

        $featuredSounds = Cache::remember('landing:featured_sounds', self::CACHE_TTL, function (): array {
            return Sound::public()
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
                ])
                ->toArray();
        });

        $featuredCreators = Cache::remember('landing:featured_creators', self::CACHE_TTL, function (): array {
            $creators = User::whereHas('sounds', function ($query): void {
                $query->public();
            })
                ->with('profile')
                ->withCount(['sounds' => fn ($q) => $q->public()])
                ->withSum(['sounds as total_plays' => fn ($q) => $q->public()], 'play_count')
                ->orderByDesc('sounds_count')
                ->take(3)
                ->get();

            // Pre-load popular sounds to avoid N+1
            $creatorIds = $creators->pluck('id')->all();
            $popularSounds = Sound::public()
                ->with('soundFile')
                ->whereIn('user_id', $creatorIds)
                ->orderByDesc('play_count')
                ->get()
                ->groupBy('user_id')
                ->map(fn ($sounds) => $sounds->first());

            return $creators->map(fn (User $user) => [
                'id' => $user->id,
                'slug' => $user->slug,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
                'location' => $user->location,
                'sounds_count' => $user->sounds_count,
                'total_plays' => $user->total_plays ?? 0,
                'featured_sound' => $popularSounds->has($user->id) ? [
                    'slug' => $popularSounds[$user->id]->slug,
                    'title' => $popularSounds[$user->id]->title,
                    'cover_url' => $popularSounds[$user->id]->cover_url,
                ] : null,
            ])->toArray();
        });

        return Inertia::render('Landing', [
            'stats' => $stats,
            'featuredSounds' => $featuredSounds,
            'featuredCreators' => $featuredCreators,
        ]);
    }
}
