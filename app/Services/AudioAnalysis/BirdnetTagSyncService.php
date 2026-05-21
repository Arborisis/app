<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

use App\Models\Sound;
use App\Models\Tag;
use Illuminate\Support\Str;

class BirdnetTagSyncService
{
    private const MIN_CONFIDENCE = 0.70;
    private const MAX_TAGS = 8;

    /**
     * Synchronise les tags d'un son à partir des détections BirdNET.
     *
     * @param array<int, array<string, mixed>> $detections
     */
    public function sync(Sound $sound, array $detections): void
    {
        $tagNames = $this->extractTagNames($detections);

        if ($tagNames === []) {
            return;
        }

        $tagIds = [];
        foreach ($tagNames as $name) {
            $tag = Tag::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
            $tagIds[] = $tag->id;
        }

        $sound->tags()->syncWithoutDetaching($tagIds);
    }

    /**
     * @param array<int, array<string, mixed>> $detections
     *
     * @return array<int, string>
     */
    private function extractTagNames(array $detections): array
    {
        $names = [];

        foreach ($detections as $detection) {
            $confidence = (float) ($detection['confidence'] ?? 0);

            if ($confidence < self::MIN_CONFIDENCE) {
                continue;
            }

            $commonName = $detection['common_name'] ?? null;
            $scientificName = $detection['scientific_name'] ?? null;

            // Priorité au nom commun s'il est disponible et lisible
            if (! empty($commonName) && ! str_contains(strtolower($commonName), 'unknown')) {
                $names[] = $this->normalizeName($commonName);
            } elseif (! empty($scientificName)) {
                // Sinon on utilise le nom scientifique comme tag technique
                $names[] = $scientificName;
            }
        }

        // Dédoublonnage en préservant l'ordre
        $unique = array_unique($names);

        return array_slice($unique, 0, self::MAX_TAGS);
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        // Capitalize première lettre de chaque mot
        return mb_convert_case($name, MB_CASE_TITLE, 'UTF-8');
    }
}
