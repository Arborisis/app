<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

use App\Enums\AnalysisStatus;
use App\Events\AudioAnalysisCompleted;
use App\Models\BirdnetDetection;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundFile;
use App\Services\Scientific\ListeningPointService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AudioAnalysisCallbackService
{
    public function __construct(
        private readonly BirdnetDetectionFilterService $detectionFilter,
        private readonly ListeningPointService $listeningPointService,
        private readonly BirdnetTagSyncService $tagSyncService,
    ) {}
    /**
     * Traite le callback du service Python Analyzer.
     *
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): SoundAnalysis
    {
        $soundId = $payload['sound_id'];
        $status = $payload['status'];
        $results = $payload['results'] ?? [];
        $errorMessage = $payload['error_message'] ?? null;
        $force = $payload['force'] ?? false;

        return DB::transaction(function () use ($soundId, $status, $results, $errorMessage, $force) {
            $sound = Sound::with('soundFile')->findOrFail($soundId);
            $analysis = SoundAnalysis::firstOrNew(['sound_id' => $sound->id]);

            // Idempotence : si déjà completed et pas de force, ignorer silencieusement
            if ($analysis->status === AnalysisStatus::COMPLETED && ! $force) {
                Log::info('AudioAnalysisCallback: analysis already completed, ignoring.', [
                    'sound_id' => $soundId,
                ]);

                return $analysis;
            }

            if ($status === 'completed') {
                $this->processCompleted($analysis, $sound, $results);
            } else {
                $this->processFailed($analysis, $sound, $errorMessage);
            }

            return $analysis->fresh('birdnetDetections');
        });
    }

    /**
     * @param array<string, mixed> $results
     */
    private function processCompleted(SoundAnalysis $analysis, Sound $sound, array $results): void
    {
        $analysis->fill([
            'status' => AnalysisStatus::COMPLETED,
            'original_r2_key' => $results['original_r2_key'] ?? $analysis->original_r2_key,
            'duration_seconds' => $results['duration_seconds'] ?? null,
            'sample_rate' => $results['sample_rate'] ?? null,
            'channels' => $results['channels'] ?? null,
            'bitrate' => $results['bitrate'] ?? null,
            'format' => $results['format'] ?? null,
            'loudness_lufs' => $results['loudness_lufs'] ?? null,
            'peak_db' => $results['peak_db'] ?? null,
            'rms_db' => $results['rms_db'] ?? null,
            'noise_floor_db' => $results['noise_floor_db'] ?? null,
            'spectral_centroid' => $results['spectral_centroid'] ?? null,
            'spectral_rolloff' => $results['spectral_rolloff'] ?? null,
            'zero_crossing_rate' => $results['zero_crossing_rate'] ?? null,
            'waveform_r2_key' => $results['waveform_r2_key'] ?? null,
            'spectrogram_r2_key' => $results['spectrogram_r2_key'] ?? null,
            'features_r2_key' => $results['features_r2_key'] ?? null,
            'birdnet_r2_key' => $results['birdnet_r2_key'] ?? null,
            'summary_r2_key' => $results['summary_r2_key'] ?? null,
            'preview_r2_key' => $results['preview_r2_key'] ?? null,
            'features_json' => $results['features_json'] ?? null,
            'features_detailed_json' => $results['features_detailed_json'] ?? null,
            'quality_label' => $results['quality_label'] ?? null,
            'quality_json' => $results['quality_json'] ?? null,
            'completed_at' => now(),
            'processed_at' => now(),
            'failed_reason' => null,
            'error_message' => null,
        ]);
        $analysis->save();

        // Met à jour la durée du sound si manquante
        if (empty($sound->duration) && ! empty($results['duration_seconds'])) {
            $sound->update(['duration' => (int) round($results['duration_seconds'])]);
        }

        // Met à jour le preview_path dans sound_files si preview générée
        if (! empty($results['preview_r2_key']) && $sound->soundFile) {
            $sound->soundFile->update([
                'radio_path' => $results['preview_r2_key'],
                'radio_mime_type' => 'audio/mpeg',
            ]);
        }

        // Crée/met à jour les détections BirdNET
        $this->syncBirdnetDetections($analysis, $sound, $results['birdnet_detections'] ?? []);

        // Génère les tags auto à partir des détections
        $this->tagSyncService->sync($sound, $results['birdnet_detections'] ?? []);

        if ($sound->listening_point_id) {
            $this->listeningPointService->refreshPointStats($sound->listeningPoint()->firstOrFail());
        }

        event(new AudioAnalysisCompleted($analysis, $sound));

        Log::info('AudioAnalysisCallback: analysis completed.', [
            'sound_id' => $sound->id,
            'analysis_id' => $analysis->id,
        ]);
    }

    private function processFailed(SoundAnalysis $analysis, Sound $sound, ?string $errorMessage): void
    {
        $analysis->markFailed('Analysis service reported failure.', $errorMessage);

        Log::warning('AudioAnalysisCallback: analysis failed.', [
            'sound_id' => $sound->id,
            'analysis_id' => $analysis->id,
            'error' => $errorMessage,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $detections
     */
    private function syncBirdnetDetections(SoundAnalysis $analysis, Sound $sound, array $detections): void
    {
        if (empty($detections)) {
            // Nettoyage si le callback renvoie un tableau vide
            BirdnetDetection::where('sound_analysis_id', $analysis->id)->delete();

            return;
        }

        // Filtre : confiance, durée, déduplication, limite max
        $filtered = $this->detectionFilter->filter($detections);

        if (empty($filtered)) {
            BirdnetDetection::where('sound_analysis_id', $analysis->id)->delete();

            Log::info('AudioAnalysisCallback: all BirdNET detections filtered out.', [
                'sound_id' => $sound->id,
                'analysis_id' => $analysis->id,
                'raw_count' => count($detections),
            ]);

            return;
        }

        // Supprime les anciennes détections pour ce sound
        BirdnetDetection::where('sound_analysis_id', $analysis->id)->delete();

        $records = [];
        foreach ($filtered as $detection) {
            $records[] = [
                'sound_id' => $sound->id,
                'scientific_name' => $detection['scientific_name'],
                'common_name' => $detection['common_name'],
                'confidence' => $detection['confidence'],
                'start_time' => $detection['start_time'] ?? 0,
                'end_time' => $detection['end_time'] ?? 0,
                'frequency_min' => $detection['frequency_min'] ?? null,
                'frequency_max' => $detection['frequency_max'] ?? null,
                'source' => $detection['source'] ?? 'birdnet',
            ];
        }

        $analysis->birdnetDetections()->createMany($records);

        Log::info('AudioAnalysisCallback: BirdNET detections synced.', [
            'sound_id' => $sound->id,
            'analysis_id' => $analysis->id,
            'raw_count' => count($detections),
            'filtered_count' => count($filtered),
        ]);
    }
}
