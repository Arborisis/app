<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

class BirdnetDetectionFilterService
{
    /**
     * Filtre et déduplique les détections BirdNET brutes.
     *
     * @param array<int, array<string, mixed>> $detections
     * @return array<int, array<string, mixed>>
     */
    public function filter(array $detections): array
    {
        $config = config('audio-analysis.birdnet', []);

        $minConfidence = (float) ($config['min_confidence'] ?? 0.25);
        $maxDetections = (int) ($config['max_detections_per_sound'] ?? 15);
        $minDuration = (float) ($config['min_detection_duration'] ?? 0.5);
        $maxDuration = (float) ($config['max_detection_duration'] ?? 60.0);
        $overlapThreshold = (float) ($config['overlap_threshold'] ?? 0.5);
        $deduplicate = (bool) ($config['deduplicate_by_species'] ?? true);

        // 1. Filtrage basique : confiance + durée
        $filtered = array_filter($detections, function (array $d) use ($minConfidence, $minDuration, $maxDuration): bool {
            $confidence = (float) ($d['confidence'] ?? 0);
            $duration = (float) ($d['end_time'] ?? 0) - (float) ($d['start_time'] ?? 0);

            return $confidence >= $minConfidence
                && $duration >= $minDuration
                && $duration <= $maxDuration;
        });

        // 2. Tri par confiance décroissante
        usort($filtered, function (array $a, array $b): int {
            $confA = (float) ($a['confidence'] ?? 0);
            $confB = (float) ($b['confidence'] ?? 0);

            return $confB <=> $confA;
        });

        // 3. Déduplication par espèce sur chevauchement temporel
        if ($deduplicate) {
            $filtered = $this->deduplicate($filtered, $overlapThreshold);
        }

        // 4. Limite max
        return \array_slice($filtered, 0, $maxDetections);
    }

    /**
     * Déduplique les détections de la même espèce qui se chevauchent temporellement.
     * Garde toujours celle avec la meilleure confiance (grâce au tri préalable).
     *
     * @param array<int, array<string, mixed>> $detections
     * @param float $overlapThreshold Seuil IoU (0.0 - 1.0)
     * @return array<int, array<string, mixed>>
     */
    private function deduplicate(array $detections, float $overlapThreshold): array
    {
        $kept = [];

        foreach ($detections as $candidate) {
            $species = (string) ($candidate['scientific_name'] ?? '');
            $start = (float) ($candidate['start_time'] ?? 0);
            $end = (float) ($candidate['end_time'] ?? 0);
            $duration = max(0.0, $end - $start);

            if ($duration <= 0.0) {
                continue;
            }

            $isDuplicate = false;

            foreach ($kept as $existing) {
                if ((string) ($existing['scientific_name'] ?? '') !== $species) {
                    continue;
                }

                $exStart = (float) ($existing['start_time'] ?? 0);
                $exEnd = (float) ($existing['end_time'] ?? 0);
                $exDuration = max(0.0, $exEnd - $exStart);

                if ($exDuration <= 0.0) {
                    continue;
                }

                $overlapStart = max($start, $exStart);
                $overlapEnd = min($end, $exEnd);
                $overlap = max(0.0, $overlapEnd - $overlapStart);
                $iou = $overlap / min($duration, $exDuration);

                if ($iou >= $overlapThreshold) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (! $isDuplicate) {
                $kept[] = $candidate;
            }
        }

        return $kept;
    }
}
