<?php

declare(strict_types=1);

namespace App\Services\Radio\Prompts;

use App\Enums\RadioDaypart;
use App\Models\RadioHostPersonality;
use App\Models\Sound;

/**
 * Pure builder: composes the system + user prompts sent to the LLM
 * to generate one short DJ intro for the radio.
 *
 * Stateless: same inputs → same prompt. Easy to unit-test.
 */
class DjScriptPromptBuilder
{
    /**
     * @param  array<int, string>  $recentOpenings  Phrases (lowercased) the LLM should NOT start with.
     * @param  array<string, mixed>|null  $previousSound  Minimal payload {title, creator, category} of the previous track.
     * @param  array<string, mixed>|null  $soundPayload   Full sound payload from RadioHostContextService::soundPayload().
     * @param  array<string, mixed>|null  $storytelling  Enriched ecology/continuity hints from StorytellingEnricher.
     * @return array{system: string, user: string}
     */
    public function build(
        Sound $sound,
        RadioHostPersonality $personality,
        RadioDaypart $daypart,
        array $recentOpenings = [],
        ?array $previousSound = null,
        ?array $soundPayload = null,
        ?array $storytelling = null,
    ): array {
        $system = $this->buildSystem($personality);
        $user = $this->buildUser($sound, $personality, $daypart, $recentOpenings, $previousSound, $soundPayload, $storytelling);

        return ['system' => $system, 'user' => $user];
    }

    private function buildSystem(RadioHostPersonality $personality): string
    {
        $brief = trim((string) $personality->prose_brief);
        $lexicon = $personality->preferred_lexicon ?? [];
        $forbidden = $personality->forbidden_phrases ?? [];

        $lexiconBlock = empty($lexicon)
            ? ''
            : "\n\nVocabulaire que tu aimes utiliser (sans en abuser) : ".implode(', ', $lexicon).'.';

        $forbiddenBlock = empty($forbidden)
            ? ''
            : "\n\nTu n'ouvres JAMAIS par ces formules : ".implode(' / ', $forbidden).'.';

        return <<<TXT
{$brief}{$lexiconBlock}{$forbiddenBlock}

Tu rédiges UNE intro radio courte, lue à voix haute par une IA vocale. Le résultat sera diffusé tel quel avant un enregistrement de field recording. Pas de markdown, pas de tiret de dialogue, pas de liste, pas de numérotation. Tu écris UN paragraphe fluide. Tu utilises éventuellement "…" pour suggérer une respiration. Tu ne mentionnes la plateforme que si c'est naturel. Tu ne nommes jamais Arborisis Radio dans ta première phrase. Tu ne demandes jamais à l'auditeur de "rester à l'écoute" ou de "ne pas manquer". Tu réponds UNIQUEMENT en JSON strict, sans markdown, sans commentaire.
TXT;
    }

    /**
     * @param  array<int, string>  $recentOpenings
     * @param  array<string, mixed>|null  $previousSound
     * @param  array<string, mixed>|null  $soundPayload
     * @param  array<string, mixed>|null  $storytelling
     */
    private function buildUser(
        Sound $sound,
        RadioHostPersonality $personality,
        RadioDaypart $daypart,
        array $recentOpenings,
        ?array $previousSound,
        ?array $soundPayload,
        ?array $storytelling,
    ): string {
        $title = trim((string) $sound->title);
        $creator = $sound->user?->name ?? 'la communauté Arborisis';
        $category = $sound->category?->name ?? 'paysage sonore';
        $duration = $sound->duration ? (int) round($sound->duration) : null;

        $speciesLine = $this->speciesLine($soundPayload);
        $locationLine = $this->locationLine($soundPayload, $sound);
        $acousticLine = $this->acousticLine($soundPayload);
        $storytellingBlock = $this->storytellingBlock($storytelling);

        $continuityBlock = $previousSound === null
            ? "Il n'y a pas de transition à faire : tu ouvres librement."
            : sprintf(
                "Tu enchaînes après une écoute : « %s » par %s. Tu peux faire une transition discrète, mais sans forcer.",
                $previousSound['title'] ?? '—',
                $previousSound['creator'] ?? 'un créateur de la communauté',
            );

        $avoidBlock = empty($recentOpenings)
            ? ''
            : "\n\nÉvite de commencer comme tes intros précédentes — voici les ouvertures que tu as déjà utilisées récemment et que tu ne dois PAS reprendre :\n- ".implode("\n- ", $recentOpenings);

        $daypartLabel = $daypart->label();

        return <<<TXT
Tu prépares ton intro pour ce moment d'antenne : {$daypartLabel}.

━━ LE SON À PRÉSENTER ━━
Titre : « {$title} »
Créateur : {$creator}
Catégorie : {$category}
Durée : {$this->durationLabel($duration)}
{$speciesLine}
{$locationLine}
{$acousticLine}
{$storytellingBlock}

━━ CONTEXTE D'ANTENNE ━━
{$continuityBlock}{$avoidBlock}

━━ FORMAT DE RÉPONSE ━━
Réponds UNIQUEMENT en JSON strict :
{
  "text": "Le texte complet de ton intro, en un seul paragraphe (entre 40 et 90 mots, soit environ 12 à 22 secondes lues). Pas de markdown.",
  "opening": "Les 8 premiers mots de ton texte, normalisés en minuscules sans ponctuation. Sert d'empreinte d'unicité.",
  "mentions": {
    "species": false,
    "location": false,
    "creator": true
  }
}

━━ CONTRAINTES ABSOLUES ━━
- Texte en français, registre cohérent avec ta personnalité.
- 40 à 90 mots. Pas plus. Pas moins.
- Pas de markdown, pas de tirets, pas de listes.
- Si tu cites une espèce, fais-le au mode probable ("ce qui pourrait être", "on y entend probablement") sauf si tu en es certain.
- Tu peux glisser une image sensorielle, une saison, une heure, un détail acoustique. Choisis UN seul angle, pas trois.
- Si un enrichissement éditorial est fourni, utilise au plus un fait écologique ou une transition ; jamais tout à la fois.
- Ton "opening" doit correspondre exactement aux 8 premiers mots de ton "text", en minuscules, sans accents conservés tels quels mais sans ponctuation.
TXT;
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function speciesLine(?array $payload): string
    {
        if ($payload === null) {
            return 'Aucune analyse acoustique disponible.';
        }

        $analysis = $payload['analysis'] ?? null;
        if (! is_array($analysis)) {
            return 'Aucune analyse acoustique disponible.';
        }

        $species = $analysis['main_detected_species'] ?? [];
        if (! is_array($species) || $species === []) {
            return 'Aucune espèce probable détectée par l\'analyse.';
        }

        $top = collect($species)
            ->take(3)
            ->map(function ($entry) {
                if (is_array($entry)) {
                    $name = $entry['common_name_fr'] ?? $entry['common_name'] ?? $entry['name'] ?? null;
                    $confidence = $entry['confidence'] ?? null;

                    if ($name === null) {
                        return null;
                    }

                    return $confidence !== null
                        ? sprintf('%s (probabilité %.0f%%)', $name, ((float) $confidence) * 100)
                        : (string) $name;
                }

                return null;
            })
            ->filter()
            ->implode(', ');

        return $top === ''
            ? 'Aucune espèce probable détectée par l\'analyse.'
            : "Espèces probables (BirdNET) : {$top}.";
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function locationLine(?array $payload, Sound $sound): string
    {
        $location = $payload['location'] ?? $sound->soundLocation?->location_name ?? null;

        return $location === null
            ? 'Lieu non précisé.'
            : "Lieu approximatif : {$location}.";
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function acousticLine(?array $payload): string
    {
        $profile = $payload['analysis']['acoustic_profile'] ?? null;
        if (! is_array($profile)) {
            return '';
        }

        $bits = [];

        if (isset($profile['spectral_centroid_hz'])) {
            $hz = (int) round((float) $profile['spectral_centroid_hz']);
            $bits[] = sprintf('centroïde spectral autour de %d Hz', $hz);
        }

        if (isset($profile['event_density'])) {
            $density = (float) $profile['event_density'];
            $bits[] = sprintf('densité d\'événements ~%.1f/s', $density);
        }

        if ($bits === []) {
            return '';
        }

        return 'Indices acoustiques : '.implode(', ', $bits).'.';
    }

    /**
     * @param  array<string, mixed>|null  $storytelling
     */
    private function storytellingBlock(?array $storytelling): string
    {
        if ($storytelling === null) {
            return '';
        }

        $lines = [];
        foreach (['transition', 'species_blurb', 'location_blurb', 'season_blurb', 'fun_fact'] as $key) {
            $value = $storytelling[$key] ?? null;
            if (is_string($value) && trim($value) !== '') {
                $lines[] = '- '.trim($value);
            }
        }

        return $lines === []
            ? ''
            : "\n━━ ENRICHISSEMENT ÉDITORIAL À UTILISER AVEC PARCIMONIE ━━\n".implode("\n", $lines);
    }

    private function durationLabel(?int $seconds): string
    {
        if ($seconds === null || $seconds <= 0) {
            return 'durée inconnue';
        }

        if ($seconds < 60) {
            return $seconds.' s';
        }

        $minutes = intdiv($seconds, 60);
        $rest = $seconds % 60;

        return $rest === 0 ? "{$minutes} min" : "{$minutes} min {$rest} s";
    }
}
