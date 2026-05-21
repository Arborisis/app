<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

use App\Enums\AnalysisStatus;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\User;
use App\Services\Storage\SignedUrlService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AudioAnalysisOrchestrationService
{
    public function __construct(
        private SignedUrlService $signedUrlService,
    ) {}

    public function retry(Sound $sound, User $user, bool $force = false): SoundAnalysis
    {
        Gate::authorize('analyze', [SoundAnalysis::class, $sound]);

        $analysis = SoundAnalysis::firstOrNew(['sound_id' => $sound->id]);

        // Si une analyse est déjà en cours et qu'on ne force pas, on retourne l'existante
        if (! $force && in_array($analysis->status, [AnalysisStatus::QUEUED, AnalysisStatus::PROCESSING], true)) {
            return $analysis;
        }

        $analysis->fill([
            'status' => AnalysisStatus::PENDING,
            'attempts' => $analysis->attempts + 1,
            'failed_reason' => null,
            'error_message' => null,
            'failed_at' => null,
            'completed_at' => null,
            'processed_at' => null,
        ]);
        $analysis->save();

        Log::info('AudioAnalysisOrchestration: retry requested.', [
            'sound_id' => $sound->id,
            'user_id' => $user->id,
            'attempt' => $analysis->attempts,
        ]);

        return $analysis;
    }

    public function getAnalysisWithUrls(Sound $sound): ?array
    {
        $analysis = SoundAnalysis::with('birdnetDetections')
            ->forSound($sound)
            ->first();

        if (! $analysis) {
            return null;
        }

        $summary = $this->summaryData($analysis);

        return [
            'id' => $analysis->id,
            'status' => $analysis->status->value,
            'attempts' => $analysis->attempts,
            'duration_seconds' => $analysis->duration_seconds,
            'sample_rate' => $analysis->sample_rate,
            'channels' => $analysis->channels,
            'bitrate' => $analysis->bitrate,
            'format' => $analysis->format,
            'loudness_lufs' => $analysis->loudness_lufs,
            'peak_db' => $analysis->peak_db,
            'rms_db' => $analysis->rms_db,
            'noise_floor_db' => $analysis->noise_floor_db,
            'spectral_centroid' => $analysis->spectral_centroid,
            'spectral_rolloff' => $analysis->spectral_rolloff,
            'zero_crossing_rate' => $analysis->zero_crossing_rate,
            'quality_label' => $analysis->quality_label,
            'quality_json' => $analysis->quality_json,
            'waveform_url' => $this->r2Url($analysis->waveform_r2_key),
            'spectrogram_url' => $this->r2Url($analysis->spectrogram_r2_key),
            'features_url' => $this->r2Url($analysis->features_r2_key),
            'birdnet_url' => $this->r2Url($analysis->birdnet_r2_key),
            'summary_url' => $this->r2Url($analysis->summary_r2_key),
            'preview_url' => $this->r2Url($analysis->preview_r2_key),
            'birdnet_detections' => $this->summarySpecies($summary, $analysis),
            'suggested_tags' => $summary['suggested_tags'] ?? [],
            'created_at' => $analysis->created_at,
            'updated_at' => $analysis->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function summaryData(SoundAnalysis $analysis): array
    {
        if (empty($analysis->summary_r2_key)) {
            return [];
        }

        try {
            if (! Storage::disk('r2')->exists($analysis->summary_r2_key)) {
                return [];
            }

            $summary = json_decode(Storage::disk('r2')->get($analysis->summary_r2_key), true);

            return is_array($summary) ? $summary : [];
        } catch (\Throwable $e) {
            Log::warning('AudioAnalysisOrchestration: unable to read summary json.', [
                'analysis_id' => $analysis->id,
                'summary_r2_key' => $analysis->summary_r2_key,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function summarySpecies(array $summary, SoundAnalysis $analysis): array
    {
        $species = $summary['main_detected_species'] ?? [];

        if (is_array($species) && ! empty($species)) {
            return collect($species)
                ->unique(fn (array $item) => $item['name'] ?? $item['common_name'] ?? null)
                ->take(5)
                ->map(fn (array $item) => [
                    'common_name' => $item['name'] ?? $item['common_name'] ?? 'Espèce détectée',
                    'scientific_name' => $item['scientific_name'] ?? null,
                    'confidence' => $item['confidence'] ?? null,
                ])
                ->values()
                ->all();
        }

        return $analysis->birdnetDetections
            ->sortByDesc('confidence')
            ->unique(fn ($d) => $d->common_name.'|'.$d->scientific_name)
            ->take(5)
            ->map(fn ($d) => [
                'scientific_name' => $d->scientific_name,
                'common_name' => $d->common_name,
                'confidence' => $d->confidence,
            ])
            ->values()
            ->all();
    }

    private function r2Url(?string $key): ?string
    {
        if (empty($key)) {
            return null;
        }

        return $this->signedUrlService->url('r2', $key);
    }
}
