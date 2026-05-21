<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Models\ArborisisPoint;

class GeoValidationService
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Calculate distance between two coordinates using Haversine formula.
     */
    public function distance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
    ): float {
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check if user is within allowed radius of the point.
     */
    public function isWithinRange(
        float $userLat,
        float $userLng,
        ArborisisPoint $point,
        ?float $customRadius = null,
    ): bool {
        $distance = $this->distance(
            $userLat,
            $userLng,
            (float) $point->latitude,
            (float) $point->longitude,
        );

        $allowedRadius = $customRadius ?? config('gamification.check_in_radius', 100);

        return $distance <= $allowedRadius;
    }

    public function getDistanceFromPoint(
        float $userLat,
        float $userLng,
        ArborisisPoint $point,
    ): float {
        return $this->distance(
            $userLat,
            $userLng,
            (float) $point->latitude,
            (float) $point->longitude,
        );
    }

    /**
     * Validate GPS accuracy. Reject if accuracy is worse than threshold.
     */
    public function isAccuracyAcceptable(?float $accuracyMeters): bool
    {
        $maxAccuracy = config('gamification.max_gps_accuracy', 50);

        if ($accuracyMeters === null) {
            return true; // No accuracy info = assume OK, but score lower
        }

        return $accuracyMeters <= $maxAccuracy;
    }
}
