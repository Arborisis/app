<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Models\ListeningPoint;
use Illuminate\Database\Eloquent\Collection;

class GeoMatchingService
{
    public function __construct(
        private int $defaultThresholdMeters = 500,
    ) {}

    public function findNearbyPoints(
        float $latitude,
        float $longitude,
        ?int $threshold = null,
        int $limit = 5,
    ): Collection {
        $threshold = $threshold ?? $this->defaultThresholdMeters;

        return $this->findWithHaversine($latitude, $longitude, $threshold, $limit);
    }

    /**
     * Trouve le point existant le plus proche et retourne la distance.
     * Retourne null si aucun point dans le seuil.
     */
    public function findClosestPoint(
        float $latitude,
        float $longitude,
        ?int $threshold = null,
    ): ?object {
        $threshold = $threshold ?? $this->defaultThresholdMeters;

        $closest = $this->findWithHaversine($latitude, $longitude, $threshold, 1)->first();

        return $closest ?? null;
    }

    private function findWithHaversine(float $lat, float $lng, int $threshold, int $limit): Collection
    {
        $earthRadius = 6371000; // mètres

        return ListeningPoint::approved()
            ->selectRaw("
                listening_points.*,
                ({$earthRadius} * acos(
                    least(1, greatest(-1,
                    cos(radians(?)) * cos(radians(public_latitude))
                    * cos(radians(public_longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(public_latitude))
                    ))
                )) AS distance_meters
            ", [$lat, $lng, $lat])
            ->whereRaw("
                ({$earthRadius} * acos(
                    least(1, greatest(-1,
                    cos(radians(?)) * cos(radians(public_latitude))
                    * cos(radians(public_longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(public_latitude))
                    ))
                )) <= ?
            ", [$lat, $lng, $lat, $threshold])
            ->orderBy('distance_meters')
            ->limit($limit)
            ->get();
    }
}
