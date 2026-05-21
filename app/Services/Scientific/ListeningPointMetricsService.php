<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Enums\MetricType;
use App\Models\BirdnetDetection;
use App\Models\ListeningPoint;
use App\Models\ScientificMetric;
use App\Models\SoundAnalysis;
use Illuminate\Support\Facades\DB;

class ListeningPointMetricsService
{
    /**
     * Calcule toutes les métriques agrégées pour un point d'écoute.
     */
    public function computeAllMetrics(ListeningPoint $point): void
    {
        $this->computeShannonDiversityIndex($point);
        $this->computeSimpsonDiversityIndex($point);
        $this->computeAcousticComplexityIndex($point);
        $this->computeTemporalTurnover($point);
        $this->computeAcousticConsistencyScore($point);
        $this->computeSpeciesRichnessMetric($point);
    }

    /**
     * Indice de Shannon : richesse ET équitabilité des espèces.
     * H' = -Σ(pi * ln(pi))
     */
    public function computeShannonDiversityIndex(ListeningPoint $point): ?ScientificMetric
    {
        $counts = $this->speciesDetectionCounts($point);

        if ($counts === []) {
            return null;
        }

        $total = array_sum($counts);
        if ($total === 0) {
            return null;
        }

        $shannon = 0.0;
        foreach ($counts as $count) {
            $p = $count / $total;
            if ($p > 0) {
                $shannon -= $p * log($p);
            }
        }

        return $this->storeMetric(
            $point,
            MetricType::BiodiversityScore,
            round($shannon, 4),
            ['method' => 'shannon', 'total_detections' => $total, 'species_count' => count($counts)],
            'shannon_index',
        );
    }

    /**
     * Indice de Simpson (1 - D) : probabilité que 2 détections soient d'espèces différentes.
     * D = Σ(ni(ni-1)) / (N(N-1))
     * 1-D = 1 - D
     */
    public function computeSimpsonDiversityIndex(ListeningPoint $point): ?ScientificMetric
    {
        $counts = $this->speciesDetectionCounts($point);

        if ($counts === []) {
            return null;
        }

        $total = array_sum($counts);
        if ($total < 2) {
            return null;
        }

        $sumNiNiMinus1 = 0;
        foreach ($counts as $count) {
            $sumNiNiMinus1 += $count * ($count - 1);
        }

        $d = $sumNiNiMinus1 / ($total * ($total - 1));
        $simpson = 1 - $d;

        return $this->storeMetric(
            $point,
            MetricType::BiodiversityScore,
            round($simpson, 4),
            ['method' => 'simpson', 'total_detections' => $total, 'species_count' => count($counts)],
            'simpson_index',
        );
    }

    /**
     * Acoustic Complexity Index (ACI) agrégé.
     * Combine l'Acoustic Diversity Index moyen et la richesse spectrale moyenne.
     */
    public function computeAcousticComplexityIndex(ListeningPoint $point): ?ScientificMetric
    {
        $analyses = SoundAnalysis::whereHas('sound', function ($q) use ($point) {
            $q->where('listening_point_id', $point->id)
                ->where('status', 'published')
                ->where('visibility', 'public');
        })
            ->whereNotNull('acoustic_diversity_index')
            ->get();

        if ($analyses->isEmpty()) {
            return null;
        }

        $adiValues = $analyses->pluck('acoustic_diversity_index')->filter()->map(fn ($v) => (float) $v);
        $centroidValues = $analyses->pluck('spectral_centroid')->filter()->map(fn ($v) => (float) $v);

        $meanAdi = $adiValues->avg() ?? 0;
        $meanCentroid = $centroidValues->avg() ?? 0;

        // Normaliser le centroid (0-10000 Hz → 0-1)
        $spectralRichness = min(1.0, $meanCentroid / 8000);

        $aci = ($meanAdi * 0.6) + ($spectralRichness * 0.4);

        return $this->storeMetric(
            $point,
            MetricType::HabitatSoundProfile,
            round($aci, 4),
            [
                'mean_adi' => round($meanAdi, 4),
                'mean_spectral_centroid_hz' => round($meanCentroid, 2),
                'spectral_richness' => round($spectralRichness, 4),
                'sample_size' => $analyses->count(),
            ],
            'acoustic_complexity_index',
        );
    }

    /**
     * Temporal Turnover : taux de remplacement des espèces dans le temps.
     * Jaccard entre la première moitié et la dernière moitié des enregistrements.
     */
    public function computeTemporalTurnover(ListeningPoint $point): ?ScientificMetric
    {
        $sounds = $point->sounds()
            ->public()
            ->whereNotNull('recorded_at')
            ->orderBy('recorded_at')
            ->get(['id', 'recorded_at']);

        if ($sounds->count() < 4) {
            return null;
        }

        $midIndex = (int) floor($sounds->count() / 2);
        $earlyIds = $sounds->slice(0, $midIndex)->pluck('id')->toArray();
        $lateIds = $sounds->slice($midIndex)->pluck('id')->toArray();

        $earlySpecies = BirdnetDetection::whereIn('sound_id', $earlyIds)
            ->where('confidence', '>=', 0.5)
            ->distinct()
            ->pluck('scientific_name')
            ->toArray();

        $lateSpecies = BirdnetDetection::whereIn('sound_id', $lateIds)
            ->where('confidence', '>=', 0.5)
            ->distinct()
            ->pluck('scientific_name')
            ->toArray();

        $intersection = count(array_intersect($earlySpecies, $lateSpecies));
        $union = count(array_unique(array_merge($earlySpecies, $lateSpecies)));

        $jaccard = $union > 0 ? $intersection / $union : 0;
        $turnover = 1 - $jaccard; // 0 = stable, 1 = completement différent

        return $this->storeMetric(
            $point,
            MetricType::TemporalChangeScore,
            round($turnover, 4),
            [
                'jaccard_similarity' => round($jaccard, 4),
                'early_species_count' => count($earlySpecies),
                'late_species_count' => count($lateSpecies),
                'early_sounds' => count($earlyIds),
                'late_sounds' => count($lateIds),
            ],
            'temporal_turnover',
        );
    }

    /**
     * Acoustic Consistency Score : stabilité du paysage sonore.
     * Variance inverse des features acoustiques clés dans le temps.
     * Score élevé = paysage sonore stable et prévisible.
     */
    public function computeAcousticConsistencyScore(ListeningPoint $point): ?ScientificMetric
    {
        $analyses = SoundAnalysis::whereHas('sound', function ($q) use ($point) {
            $q->where('listening_point_id', $point->id)
                ->where('status', 'published')
                ->where('visibility', 'public');
        })
            ->whereNotNull('spectral_centroid')
            ->whereNotNull('spectral_rolloff')
            ->whereNotNull('rms_db')
            ->get(['spectral_centroid', 'spectral_rolloff', 'rms_db']);

        if ($analyses->count() < 3) {
            return null;
        }

        $features = ['spectral_centroid', 'spectral_rolloff', 'rms_db'];
        $normalizedVariances = [];

        foreach ($features as $feature) {
            $values = $analyses->pluck($feature)->filter()->map(fn ($v) => (float) $v)->values();
            if ($values->count() < 2) {
                continue;
            }

            $mean = $values->avg();
            if ($mean == 0) {
                continue;
            }

            // Coefficient of variation (CV) = std / mean
            $variance = $values->map(fn ($v) => pow($v - $mean, 2))->avg();
            $std = sqrt($variance);
            $cv = $std / abs($mean);

            // Normaliser CV vers un score 0-1 (faible CV = haute consistance)
            $normalizedVariances[] = min(1.0, $cv);
        }

        if (empty($normalizedVariances)) {
            return null;
        }

        $meanVariance = array_sum($normalizedVariances) / count($normalizedVariances);
        $consistency = max(0.0, min(1.0, 1.0 - $meanVariance)) * 100;

        return $this->storeMetric(
            $point,
            MetricType::HabitatSoundProfile,
            round($consistency, 2),
            [
                'mean_cv' => round($meanVariance, 4),
                'features_tracked' => $features,
                'sample_size' => $analyses->count(),
            ],
            'acoustic_consistency_score',
        );
    }

    /**
     * Species Richness persistée comme métrique formelle.
     */
    public function computeSpeciesRichnessMetric(ListeningPoint $point): ?ScientificMetric
    {
        $count = BirdnetDetection::whereHas('sound', function ($q) use ($point) {
            $q->where('listening_point_id', $point->id)
                ->where('status', 'published')
                ->where('visibility', 'public');
        })
            ->where('confidence', '>=', 0.5)
            ->distinct('scientific_name')
            ->count('scientific_name');

        if ($count === 0) {
            return null;
        }

        return $this->storeMetric(
            $point,
            MetricType::BiodiversityScore,
            (float) $count,
            ['method' => 'distinct_species_count', 'confidence_threshold' => 0.5],
            'species_richness',
        );
    }

    /**
     * Retourne un tableau [scientific_name => total_detections] pour le point.
     *
     * @return array<string, int>
     */
    private function speciesDetectionCounts(ListeningPoint $point): array
    {
        return DB::table('birdnet_detections')
            ->join('sounds', 'birdnet_detections.sound_id', '=', 'sounds.id')
            ->where('sounds.listening_point_id', $point->id)
            ->where('sounds.status', 'published')
            ->where('sounds.visibility', 'public')
            ->where('birdnet_detections.confidence', '>=', 0.5)
            ->select('birdnet_detections.scientific_name', DB::raw('COUNT(*) as total'))
            ->groupBy('birdnet_detections.scientific_name')
            ->pluck('total', 'scientific_name')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    private function storeMetric(
        ListeningPoint $point,
        MetricType $type,
        float $value,
        array $components = [],
        string $periodKey = null,
    ): ScientificMetric {
        return ScientificMetric::updateOrCreate(
            [
                'measurable_type' => ListeningPoint::class,
                'measurable_id' => $point->id,
                'metric_type' => $type->value,
                'granularity' => 'overall',
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
}
