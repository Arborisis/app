<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Enums\MetricType;
use App\Models\ListeningPoint;
use App\Models\ScientificMetric;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScientificMetricService
{
    /**
     * Normalise une valeur entre 0 et 1 selon min/max.
     */
    private function normalize(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            return 0.0;
        }

        return max(0.0, min(1.0, ($value - $min) / ($max - $min)));
    }

    /**
     * Sound Biodiversity Score (SBS)
     * Quantifie la richesse biotique d'un enregistrement.
     */
    public function computeBiodiversityScore(Sound $sound): ?ScientificMetric
    {
        $analysis = $sound->soundAnalysis;

        if (! $analysis) {
            return null;
        }

        $validatedDetections = $analysis->birdnetDetections
            ->filter(fn ($d): bool => (float) $d->confidence >= 0.70);
        $speciesCounts = $validatedDetections->countBy('scientific_name');
        $speciesCount = $speciesCounts->count();
        $tagCount = $sound->tags->count();
        $adi = $analysis->acoustic_diversity_index ?? 0;
        $shannonDiversity = $this->shannonDiversity($speciesCounts->values()->all());

        // Balance spectrale favorisant la plage bioacoustique utile, sans survaloriser les extrêmes.
        $spectralCentroid = (float) ($analysis->spectral_centroid ?? 0);
        $spectralBalance = $spectralCentroid > 0
            ? max(0.0, 1.0 - min(1.0, abs($spectralCentroid - 3500.0) / 3500.0))
            : 0.0;

        $eventDensity = $this->normalize($analysis->acoustic_event_count ?? 0, 0, 50);

        $sbs = (
            0.24 * $this->normalize($speciesCount, 0, 10) +
            0.20 * $this->normalize($shannonDiversity, 0, 2.5) +
            0.18 * $this->normalize($adi, 0, 3) +
            0.13 * $this->normalize($tagCount, 0, 15) +
            0.15 * $spectralBalance +
            0.10 * $eventDensity
        ) * 100;

        return $this->storeMetric($sound, MetricType::BiodiversityScore, round($sbs, 2), [
            'species_count' => $speciesCount,
            'validated_detection_count' => $validatedDetections->count(),
            'confidence_threshold' => 0.70,
            'shannon_diversity' => round($shannonDiversity, 4),
            'tag_count' => $tagCount,
            'adi' => $adi,
            'spectral_balance' => $spectralBalance,
            'event_density' => $eventDensity,
        ]);
    }

    /**
     * Acoustic Activity Score (AAS)
     * Mesure le niveau d'activité sonore perçu.
     */
    public function computeAcousticActivityScore(Sound $sound): ?ScientificMetric
    {
        $analysis = $sound->soundAnalysis;

        if (! $analysis) {
            return null;
        }

        $loudnessLufs = (float) ($analysis->loudness_lufs ?? -60);
        $eventDensity = $analysis->acoustic_event_count ?? 0;
        $rmsDb = (float) ($analysis->rms_db ?? -60);

        $loudnessActivity = $this->normalize($loudnessLufs, -60, -12);
        $rmsActivity = $this->normalize($rmsDb, -60, -10);
        $eventActivity = $this->normalize($eventDensity, 0, 100);
        $zcrActivity = $this->normalize((float) ($analysis->zero_crossing_rate ?? 0), 0.02, 0.35);
        $silenceRatio = max(0.0, 1.0 - $eventActivity);

        $aas = (
            0.32 * $loudnessActivity +
            0.30 * $eventActivity +
            0.20 * $rmsActivity +
            0.10 * $zcrActivity +
            0.08 * (1 - $silenceRatio)
        ) * 100;

        return $this->storeMetric($sound, MetricType::AcousticActivityScore, round($aas, 2), [
            'loudness_lufs' => $loudnessLufs,
            'loudness_activity' => round($loudnessActivity, 4),
            'event_density' => $eventDensity,
            'event_activity' => round($eventActivity, 4),
            'silence_ratio' => $silenceRatio,
            'rms_db' => $rmsDb,
            'rms_activity' => round($rmsActivity, 4),
            'zcr_activity' => round($zcrActivity, 4),
        ]);
    }

    /**
     * Temporal Change Score (TCS)
     * Mesure la différence acoustique entre deux enregistrements du même lieu.
     */
    public function computeTemporalChangeScore(Sound $a, Sound $b): ?ScientificMetric
    {
        $analysisA = $a->soundAnalysis;
        $analysisB = $b->soundAnalysis;

        if (! $analysisA || ! $analysisB) {
            return null;
        }

        $features = ['spectral_centroid', 'spectral_rolloff', 'spectral_bandwidth'];
        $squaredDiffs = [];

        foreach ($features as $feature) {
            $valA = $analysisA->{$feature} ?? 0;
            $valB = $analysisB->{$feature} ?? 0;
            $max = max($valA, $valB, 1);
            $squaredDiffs[] = pow(($valB - $valA) / $max, 2);
        }

        // ADI difference
        $adiA = $analysisA->acoustic_diversity_index ?? 0;
        $adiB = $analysisB->acoustic_diversity_index ?? 0;
        $squaredDiffs[] = pow($adiB - $adiA, 2);

        // AAS difference
        $metricA = ScientificMetric::where('measurable_type', Sound::class)
            ->where('measurable_id', $a->id)
            ->where('metric_type', MetricType::AcousticActivityScore->value)
            ->first();
        $metricB = ScientificMetric::where('measurable_type', Sound::class)
            ->where('measurable_id', $b->id)
            ->where('metric_type', MetricType::AcousticActivityScore->value)
            ->first();

        $aasA = $metricA?->value ?? 50;
        $aasB = $metricB?->value ?? 50;
        $squaredDiffs[] = pow(($aasB - $aasA) / 100, 2);

        $featureDistance = sqrt(array_sum($squaredDiffs) / count($squaredDiffs));
        $tcs = $featureDistance * 100;

        // Jaccard similarity des espèces
        $speciesA = $analysisA->birdnetDetections->pluck('scientific_name')->toArray();
        $speciesB = $analysisB->birdnetDetections->pluck('scientific_name')->toArray();

        $intersection = count(array_intersect($speciesA, $speciesB));
        $union = count(array_unique(array_merge($speciesA, $speciesB)));
        $jaccard = $union > 0 ? $intersection / $union : 0;

        $tcsAdjusted = 0.7 * $tcs + 0.3 * (1 - $jaccard) * 100;

        // On stocke sur le son le plus récent
        $target = $a->recorded_at >= $b->recorded_at ? $a : $b;

        return $this->storeMetric($target, MetricType::TemporalChangeScore, round($tcsAdjusted, 2), [
            'feature_distance' => round($featureDistance, 4),
            'species_jaccard' => round($jaccard, 4),
            'compared_with_sound_id' => $target->id === $a->id ? $b->id : $a->id,
        ], 'pairwise');
    }

    /**
     * Species Presence Trend (SPT)
     * Indique si une espèce est plus ou moins présente dans le temps.
     */
    public function computeSpeciesPresenceTrend(ListeningPoint $point, string $speciesScientificName): ?ScientificMetric
    {
        $detections = DB::table('birdnet_detections')
            ->join('sounds', 'birdnet_detections.sound_id', '=', 'sounds.id')
            ->where('sounds.listening_point_id', $point->id)
            ->where('birdnet_detections.scientific_name', $speciesScientificName)
            ->where('sounds.status', 'published')
            ->selectRaw("DATE_TRUNC('month', sounds.recorded_at) as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        if ($detections->count() < 3) {
            return null;
        }

        $periods = [];
        foreach ($detections as $i => $row) {
            $periods[] = [$i, (int) $row->count];
        }

        $slope = $this->linearRegressionSlope($periods);
        $spt = max(-1.0, min(1.0, $slope * 10));

        return $this->storeMetric($point, MetricType::SpeciesPresenceTrend, round($spt, 4), [
            'species' => $speciesScientificName,
            'slope' => $slope,
            'periods_count' => $detections->count(),
        ], 'monthly', $speciesScientificName);
    }

    /**
     * Anomaly Detection (AD)
     * Z-score multivarié simplifié.
     */
    public function computeAnomalyScore(Sound $sound): ?ScientificMetric
    {
        $point = $sound->listeningPoint;

        if (! $point || $point->sounds()->public()->count() < 3) {
            return null;
        }

        $analyses = SoundAnalysis::whereHas('sound', function ($q) use ($point, $sound) {
            $q->where('listening_point_id', $point->id)
                ->where('status', 'published')
                ->where('id', '!=', $sound->id);
        })->get();

        if ($analyses->isEmpty()) {
            return null;
        }

        $features = ['spectral_centroid', 'spectral_rolloff', 'spectral_bandwidth', 'zero_crossing_rate'];
        $currentAnalysis = $sound->soundAnalysis;

        if (! $currentAnalysis) {
            return null;
        }

        $zScores = [];
        foreach ($features as $feature) {
            $values = $analyses->pluck($feature)->filter()->map(fn ($v) => (float) $v);
            $current = (float) ($currentAnalysis->{$feature} ?? 0);

            if ($values->isEmpty()) {
                continue;
            }

            $mean = $values->avg();
            $std = $this->std($values->all());

            if ($std > 0) {
                $zScores[] = abs(($current - $mean) / $std);
            }
        }

        if (empty($zScores)) {
            return null;
        }

        $anomalyScore = array_sum($zScores) / count($zScores);
        $isAnomaly = $anomalyScore > 2.5;

        return $this->storeMetric($sound, MetricType::AnomalyDetection, round($anomalyScore, 4), [
            'is_anomaly' => $isAnomaly,
            'z_scores' => $zScores,
            'threshold' => 2.5,
        ]);
    }

    /**
     * Calcule tous les métriques pour un son donné.
     */
    public function computeAllForSound(Sound $sound): void
    {
        $this->computeBiodiversityScore($sound);
        $this->computeAcousticActivityScore($sound);
        $this->computeAnomalyScore($sound);

        // TCS avec le son précédent du même point
        if ($sound->listening_point_id) {
            $previousSound = Sound::public()
                ->where('listening_point_id', $sound->listening_point_id)
                ->where('id', '!=', $sound->id)
                ->whereNotNull('recorded_at')
                ->where('recorded_at', '<', $sound->recorded_at ?? now())
                ->latest('recorded_at')
                ->first();

            if ($previousSound) {
                $this->computeTemporalChangeScore($sound, $previousSound);
            }
        }
    }

    /**
     * Calcule tous les métriques pour un point d'écoute.
     */
    public function computeAllForListeningPoint(ListeningPoint $point): void
    {
        // Tendances par espèce
        $speciesList = DB::table('birdnet_detections')
            ->join('sounds', 'birdnet_detections.sound_id', '=', 'sounds.id')
            ->where('sounds.listening_point_id', $point->id)
            ->where('sounds.status', 'published')
            ->distinct()
            ->pluck('birdnet_detections.scientific_name');

        foreach ($speciesList as $species) {
            $this->computeSpeciesPresenceTrend($point, $species);
        }
    }

    private function storeMetric(
        Sound|ListeningPoint $measurable,
        MetricType $type,
        float $value,
        array $components = [],
        string $granularity = 'overall',
        ?string $periodKey = null,
    ): ScientificMetric {
        return ScientificMetric::updateOrCreate(
            [
                'measurable_type' => $measurable::class,
                'measurable_id' => $measurable->id,
                'metric_type' => $type->value,
                'granularity' => $granularity,
                'period_key' => $periodKey,
            ],
            [
                'value' => $value,
                'components' => $components,
                'computed_at' => now(),
                'sample_size' => 1,
                'status' => 'complete',
            ]
        );
    }

    /**
     * @param array<int, array{0: int|float, 1: int|float}> $points
     */
    private function linearRegressionSlope(array $points): float
    {
        $n = count($points);
        if ($n < 2) {
            return 0.0;
        }

        $sumX = array_sum(array_column($points, 0));
        $sumY = array_sum(array_column($points, 1));
        $sumXY = array_sum(array_map(fn ($p) => $p[0] * $p[1], $points));
        $sumX2 = array_sum(array_map(fn ($p) => $p[0] * $p[0], $points));

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) {
            return 0.0;
        }

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    /**
     * @param array<int, float> $values
     */
    private function std(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn (float $v): float => pow($v - $mean, 2), $values)) / $count;

        return sqrt($variance);
    }

    /**
     * @param array<int, int> $counts
     */
    private function shannonDiversity(array $counts): float
    {
        $total = array_sum($counts);
        if ($total <= 0) {
            return 0.0;
        }

        $entropy = 0.0;
        foreach ($counts as $count) {
            if ($count <= 0) {
                continue;
            }

            $proportion = $count / $total;
            $entropy -= $proportion * log($proportion);
        }

        return $entropy;
    }
}
