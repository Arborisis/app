<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Check-in Configuration
    |--------------------------------------------------------------------------
    */

    // Maximum distance (in meters) from an Arborisis point to allow check-in
    'check_in_radius' => env('GAMIFICATION_CHECK_IN_RADIUS', 100),

    // Maximum GPS accuracy (in meters). Worse accuracy = rejected
    'max_gps_accuracy' => env('GAMIFICATION_MAX_GPS_ACCURACY', 50),

    // Cooldown between two visits of the same point (in seconds)
    'visit_cooldown' => env('GAMIFICATION_VISIT_COOLDOWN', 3600),

    // Maximum visits per day per user
    'daily_visit_limit' => env('GAMIFICATION_DAILY_VISIT_LIMIT', 20),

    // Maximum speed (in m/s) between two visits. Default: 55.56 m/s (~200 km/h)
    'max_speed_mps' => env('GAMIFICATION_MAX_SPEED_MPS', 55.56),

    /*
    |--------------------------------------------------------------------------
    | XP Configuration
    |--------------------------------------------------------------------------
    */

    'xp' => [
        'visit_validated' => 10,
        'point_created_validated' => 50,
        'sound_published' => 30,
        'report_valid' => 15,
    ],

    // Daily XP farming limit
    'daily_xp_limit' => env('GAMIFICATION_DAILY_XP_LIMIT', 500),

    /*
    |--------------------------------------------------------------------------
    | Presence Configuration
    |--------------------------------------------------------------------------
    */

    // Presence expiration in minutes
    'presence_expiry_minutes' => env('GAMIFICATION_PRESENCE_EXPIRY', 5),

    // Approximation grid size in meters for public presence
    'presence_approximation_meters' => env('GAMIFICATION_PRESENCE_APPROXIMATION', 100),

    /*
    |--------------------------------------------------------------------------
    | Nearby Interaction Configuration
    |--------------------------------------------------------------------------
    */

    // Distance (in meters) to consider two users as "nearby"
    'nearby_radius' => env('GAMIFICATION_NEARBY_RADIUS', 200),

    // Cooldown between two interactions between the same users (in seconds)
    'nearby_interaction_cooldown' => env('GAMIFICATION_NEARBY_INTERACTION_COOLDOWN', 3600),
];
