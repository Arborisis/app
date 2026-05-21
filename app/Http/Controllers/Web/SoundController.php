<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sound\StoreSoundRequest;
use App\Models\Category;
use App\Models\Environment;
use App\Models\ListeningPoint;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Services\AudioAnalysis\AudioAnalysisOrchestrationService;
use App\Services\Sound\SoundUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SoundController extends Controller
{
    public function __construct(
        private readonly SoundUploadService $uploadService,
        private readonly AudioAnalysisOrchestrationService $orchestrationService,
    ) {}

    public function index(): Response
    {
        $sounds = Sound::public()
            ->with(['user.profile', 'category', 'tags', 'soundFile'])
            ->latest()
            ->paginate(12);

        return Inertia::render('Sounds/Index', [
            'sounds' => $sounds,
            'categories' => Category::orderBy('order')->get(),
        ]);
    }

    public function show(string $slug): Response
    {
        $sound = Sound::public()
            ->with(['user.profile', 'category', 'environment', 'tags', 'soundLocation', 'soundFile'])
            ->where('slug', $slug)
            ->firstOrFail();

        $this->authorize('view', $sound);

        // Increment play count via cache counter for performance
        $cacheKey = "sound:plays:{$sound->id}";
        $playCount = \Illuminate\Support\Facades\Cache::increment($cacheKey);

        // Batch update play count every 10 plays or randomly with 5% chance
        if ($playCount >= 10 || random_int(1, 20) === 1) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, 0);
            $sound->increment('play_count', $playCount);
        }

        // Track listen for gamification asynchronously
        if (auth()->check()) {
            $user = auth()->user();
            $listenedSeconds = $sound->duration ?? 0;

            \App\Jobs\TrackSoundListen::dispatch($user->id, $sound->id, $listenedSeconds);
        }

        // Get audio URL
        $audioUrl = null;
        if ($sound->soundFile) {
            $disk = $sound->soundFile->disk;
            $audioUrl = $this->getFileUrl($disk, $sound->soundFile->path);
        }

        // Get cover URL
        $coverUrl = null;
        if ($sound->cover_image) {
            $disk = $sound->soundFile?->disk ?? 'public';
            $coverUrl = $this->getFileUrl($disk, $sound->cover_image);
        }

        $comments = $sound->comments()
            ->with(['user.profile', 'replies.user.profile'])
            ->latest()
            ->paginate(10);

        $isLiked = auth()->check()
            ? $sound->isLikedBy(auth()->user())
            : false;

        $isFollowing = auth()->check() && $sound->user
            ? auth()->user()->isFollowing($sound->user)
            : false;

        $analysis = $this->orchestrationService->getAnalysisWithUrls($sound);

        return Inertia::render('Sounds/Show', [
            'sound' => [
                ...$sound->toArray(),
                'sound_location' => $sound->soundLocation ? [
                    'public_latitude' => $sound->soundLocation->public_latitude,
                    'public_longitude' => $sound->soundLocation->public_longitude,
                    'location_name' => $sound->soundLocation->location_name,
                    'is_sensitive' => $sound->soundLocation->is_sensitive,
                ] : null,
            ],
            'audioUrl' => $audioUrl,
            'coverUrl' => $coverUrl,
            'comments' => $comments,
            'isLiked' => $isLiked,
            'isFollowing' => $isFollowing,
            'analysis' => $analysis,
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Sounds/Create', [
            'categories' => Category::orderBy('order')->get(),
            'environments' => Environment::orderBy('order')->get(),
            'selectedListeningPoint' => $this->selectedListeningPoint($request),
        ]);
    }

    public function record(Request $request): Response
    {
        return Inertia::render('Sounds/Record', [
            'categories' => Category::orderBy('order')->get(),
            'environments' => Environment::orderBy('order')->get(),
            'selectedListeningPoint' => $this->selectedListeningPoint($request),
        ]);
    }

    public function store(StoreSoundRequest $request): RedirectResponse
    {
        $this->authorize('create', Sound::class);

        try {
            $sound = $this->uploadService->upload(
                $request->validated(),
                $request->user()->id
            );

            return redirect()
                ->route('sounds.show', $sound->slug)
                ->with('success', 'Votre son a été publié avec succès.');
        } catch (\Exception $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Une erreur est survenue lors de la publication. Veuillez réessayer.');
        }
    }

    private function getFileUrl(string $disk, string $path): string
    {
        $storage = Storage::disk($disk);

        if ($disk === 'r2') {
            return app(\App\Services\Storage\SignedUrlService::class)->url($disk, $path);
        }

        if ($disk === 'audio' || $disk === 's3') {
            return $storage->temporaryUrl($path, now()->addMinutes(60));
        }

        return $storage->url($path);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function selectedListeningPoint(Request $request): ?array
    {
        $pointId = $request->integer('listening_point_id');

        if ($pointId <= 0) {
            return null;
        }

        $point = ListeningPoint::approved()
            ->with('environment')
            ->find($pointId);

        if (! $point) {
            return null;
        }

        return [
            'id' => $point->id,
            'slug' => $point->slug,
            'title' => $point->title,
            'public_latitude' => (float) $point->public_latitude,
            'public_longitude' => (float) $point->public_longitude,
            'public_accuracy_meters' => $point->public_accuracy_meters,
            'environment_id' => $point->environment_id,
            'environment' => $point->environment?->name,
        ];
    }
}
