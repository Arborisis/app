<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

use App\Models\SoundAnalysis;

class FeatureExtractorService
{
    public function getTemporalFeatures(SoundAnalysis $analysis): array
    {
        $features = $analysis->features_json ?? [];

        return [
            'duration_seconds' => $features['duration_seconds'] ?? null,
            'rms_db' => $features['rms_db'] ?? null,
            'rms_std' => $features['rms_std'] ?? null,
            'zero_crossing_rate' => $features['zero_crossing_rate'] ?? null,
            'zcr_std' => $features['zcr_std'] ?? null,
            'tempo_bpm' => $features['tempo_bpm'] ?? null,
            'beat_count' => $features['beat_count'] ?? null,
            'event_density' => $features['event_density'] ?? null,
            'silence_ratio' => $features['silence_ratio'] ?? null,
            'periodicity' => $features['periodicity'] ?? null,
        ];
    }

    public function getSpectralFeatures(SoundAnalysis $analysis): array
    {
        $features = $analysis->features_json ?? [];

        return [
            'spectral_centroid' => $features['spectral_centroid'] ?? null,
            'spectral_rolloff' => $features['spectral_rolloff'] ?? null,
            'spectral_bandwidth' => $features['spectral_bandwidth'] ?? null,
            'spectral_flatness' => $features['spectral_flatness'] ?? null,
            'spectral_entropy' => $features['spectral_entropy'] ?? null,
            'dominant_frequencies' => $features['dominant_frequencies'] ?? null,
            'low_freq_ratio' => $features['low_freq_ratio'] ?? null,
            'mid_freq_ratio' => $features['mid_freq_ratio'] ?? null,
            'high_freq_ratio' => $features['high_freq_ratio'] ?? null,
            'harmonic_ratio' => $features['harmonic_ratio'] ?? null,
            'percussive_ratio' => $features['percussive_ratio'] ?? null,
        ];
    }

    public function getCepstralFeatures(SoundAnalysis $analysis): array
    {
        $features = $analysis->features_json ?? [];

        return [
            'mfcc' => $features['mfcc'] ?? [],
            'chroma' => $features['chroma'] ?? [],
            'spectral_contrast' => $features['spectral_contrast'] ?? [],
            'tonnetz' => $features['tonnetz'] ?? [],
        ];
    }

    public function getAllFeatures(SoundAnalysis $analysis): array
    {
        return $analysis->features_json ?? [];
    }

    /**
     * Retourne un résumé public des features pour l'aperçu visiteur.
     */
    public function getPublicSummary(SoundAnalysis $analysis): array
    {
        $features = $analysis->features_json ?? [];

        return [
            'duration_seconds' => $features['duration_seconds'] ?? null,
            'rms_mean' => $features['rms_db'] ?? null,
            'zcr_mean' => $features['zero_crossing_rate'] ?? null,
            'centroid_mean' => $features['spectral_centroid'] ?? null,
            'bandwidth_mean' => $features['spectral_bandwidth'] ?? null,
            'rolloff_mean' => $features['spectral_rolloff'] ?? null,
        ];
    }
}
