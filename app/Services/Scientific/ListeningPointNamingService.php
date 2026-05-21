<?php

declare(strict_types=1);

namespace App\Services\Scientific;

use App\Models\SoundLocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class ListeningPointNamingService
{
    private const CACHE_TTL_SECONDS = 86400 * 30; // 30 jours

    private const HABITAT_PREFIXES = [
        'forest' => 'Sous-bois',
        'wetland' => 'Mare',
        'river' => 'Rive',
        'meadow' => 'Prairie',
        'ocean' => 'Littoral',
        'mountain' => 'Crête',
        'urban_nature' => 'Jardin sauvage',
        'desert' => 'Silence',
    ];

    /**
     * Génère un nom pertinent pour un point d'écoute à partir de sa localisation.
     */
    public function generateName(SoundLocation $location, ?string $habitat = null): string
    {
        // Niveau 1 : nom fourni par l'utilisateur
        if (! empty($location->location_name)) {
            return $this->sanitizeName($location->location_name);
        }

        if ($location->exact_latitude === null || $location->exact_longitude === null) {
            return $this->poeticFallback($habitat);
        }

        // Niveau 2 : reverse geocoding
        $geoName = $this->reverseGeocodeName(
            (float) $location->exact_latitude,
            (float) $location->exact_longitude,
        );

        if ($geoName !== null) {
            return $this->combineWithHabitat($geoName, $habitat);
        }

        // Niveau 3 : fallback poétique
        return $this->poeticFallback($habitat);
    }

    private function reverseGeocodeName(float $latitude, float $longitude): ?string
    {
        $cacheKey = sprintf('geocode:%.5f:%.5f', $latitude, $longitude);

        $cached = Redis::get($cacheKey);
        if ($cached !== null) {
            $data = json_decode($cached, true);

            return $data['name'] ?? null;
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Arborisis Bot / contact@arborisis.com',
            ])
                ->timeout(5)
                ->get('https://nominatim.openstreetmap.org/reverse', [
                    'format' => 'json',
                    'lat' => $latitude,
                    'lon' => $longitude,
                    'zoom' => 14,
                    'addressdetails' => 1,
                    'accept-language' => 'fr',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $address = $json['address'] ?? [];

            $name = $this->extractBestName($address);

            Redis::setex($cacheKey, self::CACHE_TTL_SECONDS, json_encode([
                'name' => $name,
                'address' => $address,
            ]));

            return $name;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extrait le nom le plus pertinent de l'adresse Nominatim.
     */
    private function extractBestName(array $address): ?string
    {
        $priority = [
            'locality',
            'hamlet',
            'village',
            'isolated_dwelling',
            'farm',
            'allotments',
            'neighbourhood',
            'suburb',
            'natural',
            'park',
            'protected_area',
            'forest',
            'water',
            'river',
            'stream',
            'mountain_pass',
            'peak',
        ];

        foreach ($priority as $key) {
            if (! empty($address[$key])) {
                $name = $this->sanitizeName($address[$key]);

                if (strlen($name) >= 2) {
                    return $name;
                }
            }
        }

        // Fallback sur la ville / région
        if (! empty($address['town'])) {
            return $this->sanitizeName($address['town']);
        }

        if (! empty($address['city'])) {
            return $this->sanitizeName($address['city']);
        }

        if (! empty($address['municipality'])) {
            return $this->sanitizeName($address['municipality']);
        }

        return null;
    }

    private function combineWithHabitat(string $geoName, ?string $habitat): string
    {
        $prefix = self::HABITAT_PREFIXES[$habitat] ?? null;

        if ($prefix === null) {
            return $geoName;
        }

        // Si le nom géo contient déjà le type d'habitat, on ne prefixe pas
        $lowerGeo = mb_strtolower($geoName);
        $habitatWords = [
            'forest' => ['forêt', 'bois', 'bosquet'],
            'wetland' => ['marais', 'marais', 'étang', 'mare', 'marais'],
            'river' => ['rivière', 'fleuve', 'ruisseau', 'cours'],
            'meadow' => ['pré', 'prairie', 'pâturage'],
            'ocean' => ['mer', 'océan', 'plage', 'littoral', 'côte'],
            'mountain' => ['mont', 'montagne', 'pic', 'sommet', 'crête', 'col'],
            'urban_nature' => ['parc', 'jardin', 'square'],
            'desert' => ['désert', 'steppe'],
        ];

        foreach ($habitatWords[$habitat] ?? [] as $word) {
            if (str_contains($lowerGeo, $word)) {
                return $geoName;
            }
        }

        return $prefix.' de '.$geoName;
    }

    private function poeticFallback(?string $habitat): string
    {
        $prefix = self::HABITAT_PREFIXES[$habitat] ?? 'Point';

        $suffixes = [
            'forest' => ['des murmures', 'des chênes', 'sauvage'],
            'wetland' => ['des grenouilles', 'nocturne', 'sauvage'],
            'river' => ['des castors', 'fluente', 'sauvage'],
            'meadow' => ['aux grillons', 'fleurie', 'sauvage'],
            'ocean' => ['des vents', 'marin', 'sauvage'],
            'mountain' => ['des aigles', 'alpine', 'sauvage'],
            'urban_nature' => ['des passants', 'urbain', 'sauvage'],
            'desert' => ['des sables', 'aride', 'sauvage'],
        ];

        $candidates = $suffixes[$habitat] ?? ['sauvage', 'inconnu'];

        return $prefix.' '.($candidates[array_rand($candidates)]);
    }

    private function sanitizeName(string $name): string
    {
        $name = trim($name);
        // Capitalize première lettre de chaque mot
        $name = mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');

        return $name;
    }
}
