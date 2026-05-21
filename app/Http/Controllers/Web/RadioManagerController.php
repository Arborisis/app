<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\RadioPodcastStatus;
use App\Enums\RadioShowType;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateRadioContent;
use App\Models\RadioDjAnnouncement;
use App\Models\RadioJingle;
use App\Models\RadioPodcast;
use App\Models\RadioSchedule;
use App\Models\RadioSetting;
use App\Models\Sound;
use App\Services\Radio\RadioPlaylistExportService;
use App\Services\Radio\RadioStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class RadioManagerController extends Controller
{
    public function __construct(
        private readonly RadioStateService $state,
        private readonly RadioPlaylistExportService $playlistExport,
    ) {}

    public function index(): InertiaResponse
    {
        $settings = RadioSetting::query()->first();

        return Inertia::render('Admin/RadioManager', [
            'station' => [
                'name' => $settings?->station_name ?? 'Arborisis Radio',
                'tagline' => $settings?->tagline,
                'engine' => $settings?->engine ?? config('radio.engine', 'icecast'),
                'public_stream_url' => $settings?->public_stream_url ?: config('radio.public_stream_url'),
                'icecast_base_url' => $settings?->icecast_base_url ?: config('radio.icecast_base_url'),
                'icecast_mount' => $settings?->icecast_mount ?: config('radio.icecast_mount'),
                'crossfade_seconds' => $settings?->crossfade_seconds ?? config('radio.crossfade_seconds', 4),
                'dj_enabled' => $settings?->dj_enabled ?? config('radio.dj.enabled', true),
                'dj_announcement_frequency' => $settings?->dj_announcement_frequency ?? config('radio.dj.announcement_frequency', 3),
                'dj_voice_provider' => $settings?->dj_voice_provider ?? config('radio.dj.voice_provider', 'elevenlabs'),
                'dj_voice_id' => $settings?->dj_voice_id ?? config('radio.dj.voice_id'),
                'discord_voice_channel_id' => $settings?->discord_voice_channel_id ?? config('radio.discord.voice_channel_id'),
                'discord_auto_join' => $settings?->discord_auto_join ?? config('radio.discord.auto_join', true),
            ],
            'status' => $this->state->status(),
            'nowPlaying' => $this->state->current(),
            'history' => $this->state->history(8),
            'metrics' => [
                'tracks' => Sound::public()->whereHas('soundFile')->count(),
                'playlist_tracks' => count($this->playlistExport->liquidsoapPlaylist()),
                'schedules' => RadioSchedule::query()->where('is_active', true)->count(),
                'jingles' => RadioJingle::query()->where('is_active', true)->count(),
                'dj_announcements' => RadioDjAnnouncement::query()->count(),
                'podcasts_published' => RadioPodcast::query()->where('status', RadioPodcastStatus::Published)->count(),
                'podcasts_pending' => RadioPodcast::query()->whereIn('status', [RadioPodcastStatus::Pending, RadioPodcastStatus::Generating])->count(),
            ],
            'playlist' => array_slice($this->playlistExport->liquidsoapPlaylist(), 0, 30),
            'jingles' => $this->playlistExport->activeJingles(),
            'podcasts' => RadioPodcast::query()
                ->orderByDesc('created_at')
                ->limit(30)
                ->get()
                ->map(fn (RadioPodcast $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'show_type' => $p->show_type?->value,
                    'status' => $p->status->value,
                    'duration' => $p->actual_duration_seconds,
                    'created_at' => $p->created_at?->toIso8601String(),
                    'published_at' => $p->published_at?->toIso8601String(),
                ]),
            'generatedSchedule' => RadioPodcast::query()
                ->readyForAir()
                ->latest('published_at')
                ->limit(10)
                ->get()
                ->map(fn (RadioPodcast $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'show_type' => $p->show_type?->value,
                    'duration' => $p->actual_duration_seconds,
                    'published_at' => $p->published_at?->toIso8601String(),
                    'insert_after_tracks' => (int) config('radio.podcast.interval_tracks', 15),
                ]),
            'schedules' => RadioSchedule::query()
                ->where('is_active', true)
                ->with('sounds:id,title,duration')
                ->orderBy('starts_at')
                ->get()
                ->map(fn (RadioSchedule $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'starts_at' => $s->starts_at?->format('H:i'),
                    'ends_at' => $s->ends_at?->format('H:i'),
                    'repeat' => $s->repeat?->value,
                    'priority' => $s->priority,
                    'sounds_count' => $s->sounds->count(),
                    'is_currently_active' => $s->isCurrentlyActive(),
                ]),
            'podcast_config' => [
                'enabled' => config('radio.podcast.enabled', false)
                    || config('radio.host.flash_enabled', false)
                    || config('radio.host.emission_enabled', false),
                'interval_tracks' => (int) config('radio.podcast.interval_tracks', 15),
            ],
        ]);
    }

    public function status(): JsonResponse
    {
        return response()->json([
            'status' => $this->state->status(),
            'now_playing' => $this->state->current(),
            'history' => $this->state->history(8),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'public_stream_url' => ['nullable', 'url', 'max:2048'],
            'icecast_base_url' => ['nullable', 'url', 'max:2048'],
            'icecast_mount' => ['required', 'string', 'max:255'],
            'crossfade_seconds' => ['required', 'integer', 'min:0', 'max:30'],
            'dj_enabled' => ['required', 'boolean'],
            'dj_announcement_frequency' => ['required', 'integer', 'min:1', 'max:20'],
            'dj_voice_id' => ['nullable', 'string', 'max:255'],
            'discord_voice_channel_id' => ['nullable', 'string', 'max:255'],
            'discord_auto_join' => ['required', 'boolean'],
        ]);

        $settings = RadioSetting::query()->firstOrCreate([], [
            'station_name' => 'Arborisis Radio',
        ]);

        $settings->update([
            ...$data,
            'engine' => 'icecast',
            'dj_voice_provider' => 'elevenlabs',
        ]);

        $this->state->requestReload();

        return back()->with('status', 'radio-settings-updated');
    }

    public function reload(): RedirectResponse
    {
        $this->state->requestReload();

        return back()->with('status', 'radio-reload-requested');
    }

    public function generateContent(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'show_type' => ['required', 'string', 'in:podcast,flash,emission'],
        ]);

        GenerateRadioContent::dispatch(RadioShowType::from($data['show_type']));

        return back()->with('status', 'radio-generation-queued');
    }

    public function publishPodcast(RadioPodcast $podcast): RedirectResponse
    {
        $podcast->update([
            'status' => RadioPodcastStatus::Published,
            'published_at' => now(),
        ]);

        $this->refreshRadioPlaylist();

        return back()->with('status', 'podcast-published');
    }

    public function rejectPodcast(RadioPodcast $podcast): RedirectResponse
    {
        $podcast->update(['status' => RadioPodcastStatus::Rejected]);
        $this->refreshRadioPlaylist();

        return back()->with('status', 'podcast-rejected');
    }

    public function deletePodcast(RadioPodcast $podcast): RedirectResponse
    {
        $podcast->delete();
        $this->refreshRadioPlaylist();

        return back()->with('status', 'podcast-deleted');
    }

    private function refreshRadioPlaylist(): void
    {
        try {
            $path = storage_path('app/radio-cache/playlist.liq');
            if (! is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            file_put_contents($path, $this->playlistExport->liq(), LOCK_EX);
            $this->state->requestReload();
        } catch (\Throwable) {
            $this->state->requestReload();
        }
    }
}
