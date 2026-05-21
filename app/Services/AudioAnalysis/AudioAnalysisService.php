<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

use App\Enums\AnalysisStatus;
use App\Enums\FrequencyScale;
use App\Enums\SpectrogramType;
use App\Events\SoundAnalyzed;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use App\Models\SoundVisualization;
use Illuminate\Support\Facades\Storage;

class AudioAnalysisService
{
    public function __construct(
        private PythonRunnerService $pythonRunner,
    ) {}

    /**
     * Récupère ou crée une analyse pour un son.
     */
    public function getOrCreateAnalysis(Sound $sound): SoundAnalysis
    {
        return SoundAnalysis::firstOrCreate(
            ['sound_id' => $sound->id],
            ['status' => AnalysisStatus::PENDING]
        );
    }

    /**
     * Lance une analyse complète (synchrone — utiliser le job pour async).
     */
    public function analyze(Sound $sound, array $config = []): SoundAnalysis
    {
        $analysis = $this->getOrCreateAnalysis($sound);

        if (! $sound->soundFile) {
            throw new \RuntimeException('Aucun fichier audio associé à ce son.');
        }

        $maxDuration = config('services.audio.max_duration', 600);
        if ($sound->duration && $sound->duration > $maxDuration) {
            throw new \RuntimeException("Durée trop longue (max {$maxDuration}s).");
        }

        $analysis->update([
            'status' => AnalysisStatus::PENDING,
            'parameters_json' => $config,
            'failed_reason' => null,
        ]);

        $tempDir = storage_path('app/temp/analysis_' . $sound->id . '_' . uniqid());
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            $localPath = $this->downloadAudioTemporarily($sound, $tempDir);
            $outputDir = $tempDir . '/output';
            mkdir($outputDir, 0755, true);

            $result = $this->pythonRunner->runAnalysisPipeline($localPath, $outputDir, $config);

            if (empty($sound->duration) && ! empty($result['audio_info']['duration'])) {
                $sound->update(['duration' => (int) round($result['audio_info']['duration'])]);
            }

            $biodiversity = $result['features']['biodiversity'] ?? [];
            $adi = $result['features']['acoustic_diversity_index'] ?? [];

            $analysis->update([
                'features_json' => $result['features'] ?? null,
                'features_detailed_json' => null,
                'status' => AnalysisStatus::COMPLETED,
                'processed_at' => now(),
                'acoustic_event_count' => $biodiversity['event_count'] ?? null,
                'acoustic_diversity_index' => $adi['adi_normalized'] ?? ($adi['adi'] ?? null),
            ]);

            $this->storeVisualizations($analysis, $outputDir, $config);

            SoundAnalyzed::dispatch($sound, $analysis);

            return $analysis->fresh('visualizations');
        } catch (\Throwable $e) {
            $analysis->markFailed($e->getMessage());
            throw $e;
        } finally {
            $this->cleanupTempDir($tempDir);
        }
    }

    /**
     * Génère une visualisation spécifique.
     */
    public function generateVisualization(SoundAnalysis $analysis, string $type, array $params = []): SoundVisualization
    {
        // Pour l'instant, les viz sont générées en batch par le pipeline.
        // Cette méthode peut être étendue pour du recalcul ciblé.
        throw new \RuntimeException('Recalcul ciblé non implémenté — relancez l\'analyse complète.');
    }

    /**
     * Exporte les features dans un format donné.
     */
    public function exportFeatures(SoundAnalysis $analysis, string $format = 'json'): array
    {
        $features = $analysis->features_json ?? [];

        if ($format === 'json') {
            return [
                'content' => json_encode($features, JSON_PRETTY_PRINT),
                'mime' => 'application/json',
                'filename' => "features_{$analysis->sound_id}.json",
            ];
        }

        if ($format === 'csv') {
            $csv = $this->featuresToCsv($features);
            return [
                'content' => $csv,
                'mime' => 'text/csv',
                'filename' => "features_{$analysis->sound_id}.csv",
            ];
        }

        throw new \InvalidArgumentException("Format d'export non supporté: {$format}");
    }

    private function downloadAudioTemporarily(Sound $sound, string $tempDir): string
    {
        $file = $sound->soundFile;
        $disk = Storage::disk($file->disk);

        $localPath = $tempDir . '/' . basename($file->path);
        $content = $disk->get($file->path);

        if ($content === null) {
            throw new \RuntimeException('Impossible de télécharger le fichier audio.');
        }

        file_put_contents($localPath, $content);

        return $localPath;
    }

    private function storeVisualizations(SoundAnalysis $analysis, string $outputDir, array $config): void
    {
        $diskName = config('filesystems.audio_disk', 'audio');
        $vizDir = "analysis/{$analysis->sound_id}";

        $vizMap = [
            'stft' => ['type' => SpectrogramType::STFT, 'file' => 'spectrogram_stft.png'],
            'mel' => ['type' => SpectrogramType::MEL_SPECTROGRAM, 'file' => 'spectrogram_mel.png'],
            'mfcc' => ['type' => SpectrogramType::MFCC, 'file' => 'spectrogram_mfcc.png'],
            'feature_correlation' => ['type' => SpectrogramType::FEATURE_CORRELATION, 'file' => 'feature_correlation.png'],
        ];

        foreach ($vizMap as $fileKey => $vizData) {
            $enumType = $vizData['type'];
            $fileName = $vizData['file'];
            $localPath = $outputDir . '/' . $fileName;

            if (! file_exists($localPath)) {
                continue;
            }

            $storedPath = $vizDir . '/' . $fileName;
            Storage::disk($diskName)->put($storedPath, file_get_contents($localPath));

            $scale = FrequencyScale::LINEAR;
            if ($fileKey === 'mel') {
                $scale = FrequencyScale::MEL;
            } elseif ($config['frequency_scale'] ?? '' === 'log') {
                $scale = FrequencyScale::LOG;
            }

            SoundVisualization::updateOrCreate(
                [
                    'sound_analysis_id' => $analysis->id,
                    'type' => $enumType->value,
                ],
                [
                    'scale' => $scale->value,
                    'file_path' => $storedPath,
                    'disk' => $diskName,
                    'parameters_json' => $config,
                ]
            );
        }
    }

    private function cleanupTempDir(string $tempDir): void
    {
        if (is_dir($tempDir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $file) {
                $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
            }
            rmdir($tempDir);
        }
    }

    private function featuresToCsv(array $features): string
    {
        $lines = [];
        foreach ($features as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subVal) {
                    if (is_array($subVal)) {
                        foreach ($subVal as $nestedKey => $nestedVal) {
                            $lines[] = "{$key}.{$subKey}.{$nestedKey}," . (is_numeric($nestedVal) ? number_format((float) $nestedVal, 6) : json_encode($nestedVal));
                        }
                    } else {
                        $lines[] = "{$key}.{$subKey}," . (is_numeric($subVal) ? number_format((float) $subVal, 6) : json_encode($subVal));
                    }
                }
            } else {
                $lines[] = "{$key}," . (is_numeric($value) ? number_format((float) $value, 6) : json_encode($value));
            }
        }
        return "feature,value\n" . implode("\n", $lines);
    }
}
