<?php

declare(strict_types=1);

namespace App\Enums;

enum MetricType: string
{
    case BiodiversityScore = 'biodiversity_score';
    case AcousticActivityScore = 'acoustic_activity_score';
    case TemporalChangeScore = 'temporal_change_score';
    case SpeciesPresenceTrend = 'species_presence_trend';
    case HabitatSoundProfile = 'habitat_sound_profile';
    case SeasonalComparison = 'seasonal_comparison';
    case TimeOfDayModel = 'time_of_day_model';
    case AnomalyDetection = 'anomaly_detection';
    case ShannonDiversityIndex = 'shannon_diversity_index';
    case SimpsonDiversityIndex = 'simpson_diversity_index';
    case AcousticComplexityIndex = 'acoustic_complexity_index';
    case TemporalTurnover = 'temporal_turnover';
    case AcousticConsistencyScore = 'acoustic_consistency_score';
    case SpeciesRichness = 'species_richness';

    public function label(): string
    {
        return match ($this) {
            self::BiodiversityScore => 'Score de biodiversité sonore',
            self::AcousticActivityScore => 'Score d\'activité acoustique',
            self::TemporalChangeScore => 'Score de changement temporel',
            self::SpeciesPresenceTrend => 'Tendance de présence d\'espèce',
            self::HabitatSoundProfile => 'Profil sonore d\'habitat',
            self::SeasonalComparison => 'Comparaison saisonnière',
            self::TimeOfDayModel => 'Modèle horaire',
            self::AnomalyDetection => 'Détection d\'anomalie',
            self::ShannonDiversityIndex => 'Indice de diversité de Shannon',
            self::SimpsonDiversityIndex => 'Indice de diversité de Simpson',
            self::AcousticComplexityIndex => 'Indice de complexité acoustique',
            self::TemporalTurnover => 'Turnover temporel des espèces',
            self::AcousticConsistencyScore => 'Score de cohérence acoustique',
            self::SpeciesRichness => 'Richesse spécifique',
        };
    }
}
