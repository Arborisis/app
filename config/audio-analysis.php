<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | BirdNET Detection Filtering
    |--------------------------------------------------------------------------
    |
    | Contrôle le nombre et la qualité des détections BirdNET stockées
    | pour chaque enregistrement. Ces valeurs limitent les faux positifs
    | et évitent de saturer la base avec des détections de faible confiance.
    |
    */

    'birdnet' => [
        // Seuil minimum de confiance (0.0 - 1.0). En dessous, la détection est ignorée.
        'min_confidence' => (float) env('BIRDNET_MIN_CONFIDENCE', 0.25),

        // Nombre maximum de détections conservées par enregistrement.
        'max_detections_per_sound' => (int) env('BIRDNET_MAX_DETECTIONS', 15),

        // Durée minimum d'une détection en secondes.
        'min_detection_duration' => (float) env('BIRDNET_MIN_DURATION', 0.5),

        // Durée maximum d'une détection en secondes.
        'max_detection_duration' => (float) env('BIRDNET_MAX_DURATION', 60.0),

        // Seuil de chevauchement temporel (0.0 - 1.0) pour dédupliquer
        // deux détections de la même espèce. 0.5 = 50% de recouvrement.
        'overlap_threshold' => (float) env('BIRDNET_OVERLAP_THRESHOLD', 0.5),

        // Activer la déduplication par espèce sur chevauchement temporel.
        'deduplicate_by_species' => (bool) env('BIRDNET_DEDUPLICATE', true),
    ],
];
