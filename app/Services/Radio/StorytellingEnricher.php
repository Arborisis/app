<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\Sound;
use App\Models\SpeciesFact;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class StorytellingEnricher
{
    public function __construct(
        private readonly RadioHostContextService $context,
    ) {}

    /**
     * @return array{
     *     species_blurb: string|null,
     *     location_blurb: string|null,
     *     season_blurb: string,
     *     transition: string,
     *     fun_fact: string|null,
     *     species: array<string, mixed>|null
     * }
     */
    public function enrich(Sound $sound, ?Sound $previous = null): array
    {
        $payload = $this->soundPayload($sound);
        $species = $this->primarySpecies($payload);
        $speciesFact = $species === null ? null : $this->speciesFact($species);

        return [
            'species_blurb' => $this->speciesBlurb($species, $speciesFact),
            'location_blurb' => $this->locationBlurb($payload, $sound),
            'season_blurb' => $this->seasonBlurb($sound->recorded_at),
            'transition' => $this->transition($sound, $previous),
            'fun_fact' => $this->funFact($payload, $speciesFact),
            'species' => $species,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function soundPayload(Sound $sound): ?array
    {
        try {
            return $this->context->soundPayload($sound);
        } catch (\Throwable $e) {
            Log::info('Radio storytelling: context unavailable', [
                'sound_id' => $sound->id,
                'reason' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function primarySpecies(?array $payload): ?array
    {
        $species = Arr::get($payload, 'analysis.main_detected_species', []);
        if (! is_array($species) || $species === []) {
            return null;
        }

        $first = array_values($species)[0] ?? null;

        return is_array($first) ? $first : null;
    }

    /**
     * @param  array<string, mixed>  $species
     */
    private function speciesFact(array $species): ?SpeciesFact
    {
        $latin = $species['latin_name'] ?? $species['scientific_name'] ?? null;
        if (is_string($latin) && trim($latin) !== '') {
            $fact = SpeciesFact::findByLatin($latin);
            if ($fact !== null) {
                return $fact;
            }
        }

        $common = $species['common_name_fr'] ?? $species['common_name'] ?? $species['name'] ?? null;
        if (is_string($common) && trim($common) !== '') {
            return SpeciesFact::findByCommonNameFr($common);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>|null  $species
     */
    private function speciesBlurb(?array $species, ?SpeciesFact $fact): ?string
    {
        if ($species === null && $fact === null) {
            return null;
        }

        $name = $fact?->common_name_fr
            ?? $species['common_name_fr']
            ?? $species['common_name']
            ?? $species['name']
            ?? null;

        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        $confidence = isset($species['confidence']) ? (float) $species['confidence'] : null;
        $certainty = $confidence !== null && $confidence >= 0.85 ? 'identifiée avec une bonne confiance' : 'probablement présente';

        return $fact?->fact_fr
            ? "{$name}, {$certainty} : {$fact->fact_fr}"
            : "{$name} est {$certainty} dans l'analyse acoustique.";
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function locationBlurb(?array $payload, Sound $sound): ?string
    {
        $location = Arr::get($payload, 'location') ?? $sound->soundLocation?->location_name;

        return is_string($location) && trim($location) !== ''
            ? "Lieu approximatif à évoquer avec prudence : {$location}."
            : null;
    }

    private function seasonBlurb(mixed $recordedAt): string
    {
        $date = $recordedAt instanceof CarbonInterface ? $recordedAt : now();
        $month = (int) $date->format('n');

        $season = match (true) {
            in_array($month, [3, 4, 5], true) => 'printemps',
            in_array($month, [6, 7, 8], true) => 'été',
            in_array($month, [9, 10, 11], true) => 'automne',
            default => 'hiver',
        };

        return "Saison d'écoute : {$season}, au mois de ".$date->translatedFormat('F').'.';
    }

    private function transition(Sound $sound, ?Sound $previous): string
    {
        if ($previous === null) {
            return 'Ouverture autonome : installer le lieu et entrer directement dans le son.';
        }

        return sprintf(
            'Transition possible depuis « %s » vers « %s », sans surjouer le lien.',
            trim((string) $previous->title),
            trim((string) $sound->title),
        );
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function funFact(?array $payload, ?SpeciesFact $fact): ?string
    {
        if ($fact?->habitat) {
            return "Habitat associé : {$fact->habitat}.";
        }

        $density = Arr::get($payload, 'analysis.acoustic_profile.event_density');
        if (is_numeric($density)) {
            return sprintf('Densité acoustique relevée : environ %.1f événement(s) par seconde.', (float) $density);
        }

        return null;
    }
}
