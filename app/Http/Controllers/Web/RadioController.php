<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RadioPodcast;
use App\Models\RadioReaction;
use App\Models\RadioChannel;
use App\Models\RadioSchedule;
use App\Models\RadioSetting;
use App\Services\Radio\RadioAudioCacheService;
use App\Services\Radio\RadioStateService;
use App\Services\Radio\RadioStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RadioController extends Controller
{
    public function __construct(
        private readonly RadioStreamService $streamService,
        private readonly RadioStateService $stateService,
    ) {}

    public function index(?RadioChannel $channel = null): InertiaResponse
    {
        $nowPlaying = $this->stateService->current($this->streamService);
        $history = collect($this->stateService->history(5));
        if ($history->isEmpty()) {
            $history = $this->streamService->getHistorySounds(5)->map(fn ($sound) => [
                'id' => $sound->id,
                'title' => $sound->title,
                'slug' => $sound->slug,
                'user_name' => $sound->user?->name,
                'cover_url' => $sound->cover_url,
                'duration' => $sound->duration,
            ]);
        }
        $listenerCount = $this->streamService->getListenerCount();

        return Inertia::render('Radio/Index', [
            'nowPlaying' => $nowPlaying,
            'history' => $history->map(fn ($item) => [
                'id' => $item['id'] ?? $item['sound_id'] ?? null,
                'title' => $item['title'] ?? null,
                'slug' => $item['slug'] ?? null,
                'user_name' => $item['user_name'] ?? $item['artist'] ?? null,
                'cover_url' => $item['cover_url'] ?? $item['cover'] ?? null,
                'duration' => $item['duration'] ?? null,
            ])->values(),
            'listenerCount' => $listenerCount,
            'channels' => $this->channelsPayload(),
            'activeChannel' => $channel?->slug ?? 'main',
        ]);
    }

    public function shows(): InertiaResponse
    {
        $items = RadioPodcast::query()
            ->published()
            ->whereNotNull('path')
            ->latest('published_at')
            ->latest('created_at')
            ->get()
            ->map(fn (RadioPodcast $podcast) => [
                'id' => $podcast->id,
                'show_type' => $podcast->show_type?->value ?? 'podcast',
                'title' => $podcast->title,
                'description' => $podcast->description,
                'theme' => $podcast->theme,
                'duration' => $podcast->actual_duration_seconds,
                'audio_url' => route('radio.cache.serve', ['type' => 'podcasts', 'id' => $podcast->id]),
                'published_at' => $podcast->published_at?->toIso8601String(),
            ]);

        return Inertia::render('Radio/Shows', [
            'items' => $items,
            'counts' => [
                'all' => $items->count(),
                'podcast' => $items->where('show_type', 'podcast')->count(),
                'flash' => $items->where('show_type', 'flash')->count(),
                'emission' => $items->where('show_type', 'emission')->count(),
            ],
        ]);
    }

    public function stream(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $publicStreamUrl = config('radio.public_stream_url');
        $settings = RadioSetting::query()->first();
        $publicStreamUrl = $settings?->public_stream_url ?: $publicStreamUrl;
        $engine = $settings?->engine ?: config('radio.engine', 'icecast');

        if ($engine === 'icecast' && ! empty($publicStreamUrl)) {
            return redirect()->away($publicStreamUrl);
        }

        set_time_limit(0);
        ignore_user_abort(true);

        $mimeType = $this->streamService->getPlaylistMimeType();

        $headers = [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
            'Access-Control-Allow-Origin' => '*',
            'X-Accel-Buffering' => 'no',
        ];

        $icyRequested = $request->headers->get('Icy-MetaData') === '1';

        if ($icyRequested && $this->streamService->supportsIcyMetadata()) {
            $headers['icy-metaint'] = (string) config('radio.icy_metaint', 8192);
            $headers['icy-name'] = 'Arborisis Radio';
            $headers['icy-genre'] = 'Field Recording / Nature';
            $headers['icy-url'] = config('app.url');
        }

        return response()->stream(function () use ($icyRequested) {
            if (app()->environment('testing')) {
                echo 'test-stream';

                return;
            }

            try {
                $this->streamService->streamToOutput(function (string $chunk): void {
                    echo $chunk;
                }, $icyRequested);
            } catch (\Throwable $e) {
                Log::error('Radio stream error', ['exception' => $e]);
            }
        }, 200, $headers);
    }

    public function playlist(): \Illuminate\Http\Response
    {
        $settings = RadioSetting::query()->first();
        $streamUrl = $settings?->public_stream_url ?: config('radio.public_stream_url') ?: route('radio.stream');
        $content = "#EXTM3U\n#EXTINF:-1,Arborisis Radio\n{$streamUrl}\n";

        return response($content, 200, [
            'Content-Type' => 'audio/x-mpegurl',
            'Content-Disposition' => 'attachment; filename="arborisis-radio.m3u"',
        ]);
    }

    public function nowPlaying(): \Illuminate\Http\JsonResponse
    {
        $metadata = $this->stateService->current($this->streamService);
        $listenerCount = $this->streamService->getListenerCount();
        $nextSound = $this->streamService->resolveNextSound();
        $nextUp = $metadata['next_up'] ?? null;

        return response()->json([
            'now_playing' => $metadata,
            'next_up' => $nextUp ?: ($nextSound ? [
                'id' => $nextSound->id,
                'title' => $nextSound->title,
                'slug' => $nextSound->slug,
                'user_name' => $nextSound->user?->name,
                'cover_url' => $nextSound->cover_url,
                'duration' => $nextSound->duration,
            ] : null),
            'listener_count' => $listenerCount,
            'reactions_summary' => $this->reactionSummary((int) ($metadata['sound_id'] ?? 0)),
            'updated_at' => now()->toIso8601String(),
        ]);
    }

    public function programme(Request $request): \Illuminate\Http\JsonResponse
    {
        $date = $request->date('date') ?? now();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $schedules = RadioSchedule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('starts_at', [$start, $end])
                    ->orWhere(function ($query) use ($start, $end) {
                        $query->where('starts_at', '<=', $end)
                            ->where(function ($query) use ($start) {
                                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $start);
                            });
                    });
            })
            ->orderBy('starts_at')
            ->limit(8)
            ->get()
            ->map(fn (RadioSchedule $schedule) => [
                'id' => $schedule->id,
                'type' => 'schedule',
                'title' => $schedule->name,
                'description' => $schedule->description,
                'starts_at' => $schedule->starts_at?->toIso8601String(),
                'ends_at' => $schedule->ends_at?->toIso8601String(),
            ]);

        $shows = RadioPodcast::query()
            ->published()
            ->whereNotNull('path')
            ->whereBetween('published_at', [$start->copy()->subHours(12), $end])
            ->latest('published_at')
            ->limit(8)
            ->get()
            ->map(fn (RadioPodcast $podcast) => [
                'id' => $podcast->id,
                'type' => $podcast->show_type?->value ?? 'podcast',
                'title' => $podcast->title,
                'description' => $podcast->description,
                'starts_at' => $podcast->published_at?->toIso8601String() ?? $podcast->created_at?->toIso8601String(),
                'ends_at' => null,
            ]);

        return response()->json([
            'date' => $date->toDateString(),
            'items' => $schedules
                ->concat($shows)
                ->sortBy('starts_at')
                ->values()
                ->take(12),
        ]);
    }

    public function channels(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'items' => $this->channelsPayload(),
        ]);
    }

    public function serveCachedAudio(string $type, int $id, RadioAudioCacheService $cache): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        if (! in_array($type, ['sounds', 'jingles', 'podcasts', 'dj'], true)) {
            return response()->json(['error' => 'Invalid type'], 404);
        }

        if (! $cache->exists($type, $id)) {
            return response()->json(['error' => 'Not found'], 404);
        }

        $path = $cache->pathFor($type, $id);

        return response()->file($path, [
            'Content-Type' => 'audio/mpeg',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function reactionSummary(int $soundId): array
    {
        if ($soundId <= 0) {
            return ['like' => 0, 'heart' => 0, 'leaf' => 0, 'star' => 0];
        }

        $likeCount = (int) (\App\Models\Sound::query()->whereKey($soundId)->value('like_count') ?? 0);

        if (! \Illuminate\Support\Facades\Schema::hasTable('radio_reactions')) {
            return ['like' => $likeCount, 'heart' => 0, 'leaf' => 0, 'star' => 0];
        }

        $counts = RadioReaction::query()
            ->where('sound_id', $soundId)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->selectRaw('reaction_type, COUNT(*) as aggregate_count')
            ->groupBy('reaction_type')
            ->pluck('aggregate_count', 'reaction_type')
            ->map(fn ($count) => (int) $count)
            ->all();

        return [
            'like' => $likeCount,
            'heart' => (int) ($counts['heart'] ?? 0),
            'leaf' => (int) ($counts['leaf'] ?? 0),
            'star' => (int) ($counts['star'] ?? 0),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function channelsPayload(): array
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('radio_channels')) {
            return [];
        }

        return RadioChannel::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (RadioChannel $channel) => [
                'id' => $channel->id,
                'slug' => $channel->slug,
                'name' => $channel->name,
                'mount_path' => $channel->mount_path,
                'color' => $channel->color,
                'description' => $channel->description,
                'url' => $channel->slug === 'main' ? url('/radio') : url('/radio/c/'.$channel->slug),
            ])
            ->values()
            ->all();
    }
}
