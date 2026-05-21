<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;
use App\Services\Scientific\ScientificStatsService;
use Illuminate\Support\Facades\Storage;

class RadioHostContextService
{
    public function __construct(
        private readonly ScientificStatsService $stats,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function gather(): array
    {
        $globalStats = $this->stats->getGlobalStats();

        return [
            'new_sounds'      => $this->newSounds(),
            'new_users'       => $this->newUsers(),
            'popular_sounds'  => $this->popularSounds(),
            'total_sounds'    => $globalStats['total_sounds'],
            'total_creators'  => $globalStats['total_creators'],
            'categories'      => $this->stats->getCategoryDistribution(),
            'generated_at'    => now()->toIso8601String(),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function newSounds(int $days = 7): array
    {
        return Sound::public()
            ->with(['user', 'category', 'soundLocation', 'soundAnalysis.birdnetDetections'])
            ->where('created_at', '>=', now()->subDays($days))
            ->latest('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Sound $s) => $this->soundPayload($s))
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function newUsers(int $days = 7): array
    {
        return User::query()
            ->where('created_at', '>=', now()->subDays($days))
            ->with('profile')
            ->withCount('sounds')
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (User $u) => [
                'name'        => $u->name,
                'sounds_count' => $u->sounds_count ?? 0,
            ])
            ->values()
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function popularSounds(int $limit = 5): array
    {
        return Sound::public()
            ->with(['user', 'category', 'soundLocation', 'soundAnalysis.birdnetDetections'])
            ->orderByDesc('play_count')
            ->limit($limit)
            ->get()
            ->map(fn (Sound $s) => $this->soundPayload($s))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    public function soundPayload(Sound $sound): array
    {
        return [
            'id'         => $sound->id,
            'title'      => $sound->title,
            'creator'    => $sound->user?->name ?? 'Arborisis',
            'category'   => $sound->category?->name,
            'location'   => $sound->soundLocation?->location_name,
            'duration'   => $sound->duration,
            'play_count' => $sound->play_count ?? 0,
            'like_count' => $sound->like_count ?? 0,
            'analysis'   => $this->analysisPayload($sound->soundAnalysis),
        ];
    }

    /** @return array<string, mixed>|null */
    public function previousTrackContext(): ?array
    {
        $previous = app(RadioStateService::class)->history(1)[0] ?? null;

        if (! is_array($previous) || empty($previous['sound_id'])) {
            return null;
        }

        return [
            'id' => (int) $previous['sound_id'],
            'title' => (string) ($previous['title'] ?? ''),
            'creator' => (string) ($previous['artist'] ?? 'la communauté Arborisis'),
            'played_at' => $previous['played_at'] ?? null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function analysisPayload(?SoundAnalysis $analysis): ?array
    {
        if (! $analysis) {
            return null;
        }

        $summary = $this->loadSummary($analysis);
        $features = $analysis->features_json ?? [];

        return [
            'main_detected_species' => $summary['main_detected_species'] ?? $this->speciesFromDetections($analysis),
            'suggested_tags' => $summary['suggested_tags'] ?? [],
            'acoustic_profile' => $summary['acoustic_profile'] ?? [
                'spectral_centroid_hz' => $analysis->spectral_centroid ?? ($features['spectral_centroid'] ?? null),
                'spectral_rolloff_hz' => $analysis->spectral_rolloff ?? ($features['spectral_rolloff'] ?? null),
                'zero_crossing_rate' => $analysis->zero_crossing_rate ?? ($features['zero_crossing_rate'] ?? null),
                'event_density' => $features['event_density'] ?? null,
            ],
            'quality' => $summary['quality'] ?? [
                'label' => $analysis->quality_label,
                'noise_floor_db' => $analysis->noise_floor_db,
                'loudness_lufs' => $analysis->loudness_lufs,
            ],
            'spectrogram' => [
                'available' => $analysis->spectrogram_r2_key !== null,
                'r2_key' => $analysis->spectrogram_r2_key,
            ],
            'summary_r2_key' => $analysis->summary_r2_key,
            'birdnet_r2_key' => $analysis->birdnet_r2_key,
        ];
    }

    /** @return list<array{name: string, confidence: float}> */
    private function speciesFromDetections(SoundAnalysis $analysis): array
    {
        return $analysis->birdnetDetections
            ->sortByDesc('confidence')
            ->unique('common_name')
            ->take(5)
            ->map(fn ($detection) => [
                'name' => $detection->common_name,
                'confidence' => (float) $detection->confidence,
            ])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function loadSummary(SoundAnalysis $analysis): array
    {
        if (! $analysis->summary_r2_key) {
            return [];
        }

        foreach (array_unique(['r2', (string) config('radio.host.storage_disk', 'r2')]) as $disk) {
            try {
                if (Storage::disk($disk)->exists($analysis->summary_r2_key)) {
                    $summary = json_decode(Storage::disk($disk)->get($analysis->summary_r2_key), true);

                    return is_array($summary) ? $summary : [];
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return [];
    }
}
