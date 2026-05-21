<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Models\ListeningPoint;
use App\Models\Sound;
use App\Models\SoundLocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ListeningPointService
{
    public function __construct(
        private GeoMatchingService $geoMatching,
        private ListeningPointNamingService $namingService,
        private ListeningPointMetricsService $metricsService,
    ) {}

    /**
     * Crée un nouveau point d'écoute à partir d'un son.
     */
    public function createFromSound(Sound $sound, array $overrides = []): ListeningPoint
    {
        $location = $sound->soundLocation;

        if (! $location) {
            throw new \RuntimeException('Le son n\'a pas de localisation.');
        }

        $sensitivity = $overrides['nature_sensitivity_level']
            ?? NatureSensitivityLevel::Normal;

        $publicCoords = ListeningPoint::publicCoordinates(
            (float) $location->exact_latitude,
            (float) $location->exact_longitude,
            $sensitivity,
        );

        return DB::transaction(function () use ($sound, $location, $publicCoords, $overrides, $sensitivity) {
            $point = ListeningPoint::create([
                'creator_user_id' => $sound->user_id,
                'title' => $overrides['title'] ?? $this->namingService->generateName($location, $overrides['habitat_type'] ?? null),
                'description' => $overrides['description'] ?? $sound->description,
                'exact_latitude' => $location->exact_latitude,
                'exact_longitude' => $location->exact_longitude,
                'public_latitude' => $publicCoords['public_latitude'],
                'public_longitude' => $publicCoords['public_longitude'],
                'public_accuracy_meters' => $publicCoords['public_accuracy_meters'],
                'environment_id' => $sound->environment_id,
                'habitat_type' => $overrides['habitat_type'] ?? null,
                'moderation_status' => ModerationStatus::Approved,
                'approved_at' => now(),
                'nature_sensitivity_level' => $sensitivity,
                'recordings_count' => 1,
                'first_recorded_at' => $sound->recorded_at,
                'last_recorded_at' => $sound->recorded_at,
            ]);

            $location->update(['listening_point_id' => $point->id]);
            $sound->update(['listening_point_id' => $point->id]);

            $this->metricsService->computeAllMetrics($point);

            return $point;
        });
    }

    /**
     * Attache un son existant à un point d'écoute existant.
     */
    public function attachSoundToPoint(Sound $sound, ListeningPoint $point): void
    {
        DB::transaction(function () use ($sound, $point) {
            $sound->update(['listening_point_id' => $point->id]);

            if ($sound->soundLocation) {
                $sound->soundLocation->update(['listening_point_id' => $point->id]);
            }

            $this->refreshPointStats($point);
            $this->metricsService->computeAllMetrics($point);
        });
    }

    /**
     * Propose un point d'écoute proche lors de la publication d'un son.
     * Retourne : [suggestion_found => bool, point => ListeningPoint|null, distance_meters => float|null]
     */
    public function suggestPointForSound(Sound $sound, ?int $threshold = null): array
    {
        $location = $sound->soundLocation;

        if (! $location || ! $location->exact_latitude || ! $location->exact_longitude) {
            return ['suggestion_found' => false, 'point' => null, 'distance_meters' => null];
        }

        $closest = $this->geoMatching->findClosestPoint(
            (float) $location->exact_latitude,
            (float) $location->exact_longitude,
            $threshold,
        );

        if (! $closest) {
            return ['suggestion_found' => false, 'point' => null, 'distance_meters' => null];
        }

        return [
            'suggestion_found' => true,
            'point' => $closest,
            'distance_meters' => (float) $closest->distance_meters,
        ];
    }

    /**
     * Recalcule les statistiques dénormalisées d'un point d'écoute.
     */
    public function refreshPointStats(ListeningPoint $point): void
    {
        $sounds = $point->sounds()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->get();

        $recordingsCount = $sounds->count();
        $firstRecordedAt = $sounds->min('recorded_at');
        $lastRecordedAt = $sounds->max('recorded_at');

        // Compte des espèces détectées distinctes
        $speciesCount = \App\Models\BirdnetDetection::whereIn('sound_id', $sounds->pluck('id'))
            ->distinct('scientific_name')
            ->count('scientific_name');

        // Tags dominants
        $tagCounts = [];
        foreach ($sounds as $sound) {
            foreach ($sound->tags as $tag) {
                $tagCounts[$tag->name] = ($tagCounts[$tag->name] ?? 0) + 1;
            }
        }
        arsort($tagCounts);
        $dominantTags = array_slice($tagCounts, 0, 10, true);

        $point->update([
            'recordings_count' => $recordingsCount,
            'species_detected_count' => $speciesCount,
            'first_recorded_at' => $firstRecordedAt,
            'last_recorded_at' => $lastRecordedAt,
            'dominant_tags' => $dominantTags,
        ]);
    }

    /**
     * Fusionne deux points d'écoute (admin uniquement).
     */
    public function mergePoints(ListeningPoint $target, ListeningPoint $source): void
    {
        DB::transaction(function () use ($target, $source) {
            // Déplacer tous les sound_locations
            SoundLocation::where('listening_point_id', $source->id)
                ->update(['listening_point_id' => $target->id]);

            // Déplacer tous les sons
            Sound::where('listening_point_id', $source->id)
                ->update(['listening_point_id' => $target->id]);

            // Déplacer les observations environnementales
            \App\Models\EnvironmentalObservation::where('listening_point_id', $source->id)
                ->update(['listening_point_id' => $target->id]);

            // Soft delete du source
            $source->delete();

            // Recalculer les stats
            $this->refreshPointStats($target);
        });
    }
}
