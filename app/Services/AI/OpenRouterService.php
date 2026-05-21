<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Sound;
use App\Services\CircuitBreaker;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    private const DEFAULT_MODEL = 'moonshotai/kimi-k2.6';

    private const BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * Génère un thème unique de podcast pour la semaine, avec recherche web.
     *
     * @param  list<string>  $recentThemes  Thèmes déjà utilisés pour éviter les répétitions
     */
    public function generatePodcastTopic(array $recentThemes): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('services.openrouter.model', self::DEFAULT_MODEL);
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('OpenRouter: missing API key for topic generation');

            return null;
        }

        $themesList = empty($recentThemes)
            ? 'Aucun thème précédent.'
            : 'Thèmes déjà traités : '.implode(', ', $recentThemes).'.';

        $prompt = <<<PROMPT
Tu es un créateur de contenu radio spécialisé en field recording et sons de la nature.

{$themesList}

Génère une idée de vrai podcast original pour cette semaine. Le thème doit être :
- En lien avec le field recording, la nature, l'écologie sonore, ou les paysages acoustiques
- Différent des thèmes déjà utilisés ci-dessus
- Inspirant et poétique
- Assez riche pour produire un épisode narratif de 10 à 20 minutes, avec plusieurs chapitres

Utilise la recherche web pour trouver un angle d'actualité, une découverte récente, ou un phénomène naturel intéressant lié au field recording.

Réponds UNIQUEMENT en JSON strict sans markdown :
{
  "theme": "Titre du thème",
  "angle": "Angle original et pourquoi il est pertinent cette semaine",
  "research_summary": "Résumé des découvertes web utilisées",
  "keywords": ["mot-clé1", "mot-clé2"]
}
PROMPT;

        try {
            $response = $this->postChatCompletion($apiKey, $baseUrl, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un journaliste culturel et naturaliste. Tu conçois de vrais sujets de podcasts narratifs, documentaires et fouillés. Tu as accès à la recherche web. Réponds UNIQUEMENT en JSON strict.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'tools' => [
                    ['type' => 'openrouter:web_search'],
                ],
                'temperature' => 0.8,
                'max_tokens' => 2048,
            ]);

            if (! $response->successful()) {
                Log::warning('OpenRouter: topic generation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                Log::warning('OpenRouter: empty content in topic response');

                return null;
            }

            $topic = json_decode($content, true);

            if (! is_array($topic) || empty($topic['theme'])) {
                Log::warning('OpenRouter: invalid topic schema', ['content' => $content]);

                return null;
            }

            $usage = $data['usage'] ?? [];
            $cost = $data['usage']['total_cost'] ?? null;

            Log::info('OpenRouter: podcast topic generated', [
                'theme' => $topic['theme'],
                'model' => $model,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ]);

            return [
                'topic' => $topic,
                'usage' => $usage,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenRouter: exception during topic generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function generatePodcastScript(array $sounds, array $topicData): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('services.openrouter.model', self::DEFAULT_MODEL);
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('OpenRouter: missing API key');

            return null;
        }

        $prompt = $this->buildPrompt($sounds, $topicData);

        try {
            $response = $this->postChatCompletion($apiKey, $baseUrl, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un auteur de podcasts documentaires français spécialisé en field recording, nature et écologie sonore. Tu écris de vrais épisodes narratifs avec une progression dramatique, des respirations et une voix incarnée. Ton style unit la précision naturaliste et la sensibilité poétique — proche de Brice Couturier ou Laurent Joffrin pour la rigueur, proche de Jean-Nicolas Léo ou d\'Olivier Rolin pour la chair sonore. Tu utilises les recherches web pour enrichir ton propos. Chaque segment de ton script est autonome car il sera synthétisé séparément par une IA vocale. Réponds UNIQUEMENT en JSON strict, sans markdown, sans commentaire.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'tools' => [
                    ['type' => 'openrouter:web_search'],
                ],
                'temperature' => 0.7,
                'max_tokens' => 8192,
            ]);

            if (! $response->successful()) {
                Log::warning('OpenRouter: script generation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                Log::warning('OpenRouter: empty content in response');

                return null;
            }

            $script = json_decode($content, true);

            if (! is_array($script)) {
                Log::warning('OpenRouter: invalid JSON for podcast script', ['content' => $content]);

                return null;
            }

            // Fallback: compute estimated duration from segment texts if the model forgot the field
            if (empty($script['estimated_duration_seconds']) && ! empty($script['segments'])) {
                $totalWords = array_sum(array_map(
                    fn ($s) => str_word_count((string) ($s['text'] ?? '')),
                    array_filter($script['segments'], fn ($s) => ! empty($s['text']))
                ));
                $script['estimated_duration_seconds'] = (int) round($totalWords / 2.17); // ~130 wpm in French
            }

            if (! $this->validateScriptSchema($script)) {
                Log::warning('OpenRouter: invalid script schema', ['content' => $content]);

                return null;
            }

            $usage = $data['usage'] ?? [];
            $cost = $data['usage']['total_cost'] ?? null;

            Log::info('OpenRouter: podcast script generated', [
                'model' => $model,
                'prompt_tokens' => $usage['prompt_tokens'] ?? null,
                'completion_tokens' => $usage['completion_tokens'] ?? null,
                'estimated_cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ]);

            return [
                'script' => $script,
                'usage' => $usage,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenRouter: exception during generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function buildPrompt(array $sounds, array $topicData): string
    {
        $soundsData = [];
        foreach ($sounds as $sound) {
            $soundsData[] = $this->soundDataForScript($sound);
        }

        $jsonSounds = json_encode($soundsData, JSON_UNESCAPED_UNICODE);
        $theme = $topicData['theme'] ?? 'Field recording et nature';
        $angle = $topicData['angle'] ?? '';
        $research = $topicData['research_summary'] ?? '';
        $brief = $this->dynamicEditorialBrief('podcast');

        $minDuration = (int) config('radio.podcast.min_duration', 600);
        $maxDuration = (int) config('radio.podcast.max_duration', 1200);

        return <<<PROMPT
Tu vas écrire un vrai épisode de podcast narratif documentaire pour Arborisis Radio — pas une capsule courte, pas un bulletin.
Durée cible : {$minDuration} à {$maxDuration} secondes (environ 10 à 20 minutes au rythme oral naturel).

━━ BRIEF ÉDITORIAL ━━
Moment d'antenne : {$brief['daypart']}
Saison ressentie : {$brief['season']}
Angle de réalisation : {$brief['angle']}
Style d'ouverture : {$brief['opening']}
Rythme narratif : {$brief['rhythm']}
Image sonore dominante : {$brief['sound_image']}
À éviter cette fois : {$brief['avoid']}

━━ SUJET ━━
Thème : {$theme}
Angle issu de la recherche : {$angle}
Contexte de recherche : {$research}

━━ SONS DISPONIBLES ━━
{$jsonSounds}

Les champs "analysis" contiennent les résultats du pipeline Arborisis : détections BirdNET (à citer comme espèces probables, jamais comme certitudes), profil acoustique (centroïde spectral, densité d'événements, ratio harmonique, plage dynamique), qualité (LUFS, plancher de bruit). Exploite ces indices de façon narrative : parle de textures, de fréquences dominantes, de moments d'écoute particuliers.

━━ RÈGLES D'ÉCRITURE POUR LA VOIX ━━
- Chaque segment est synthétisé séparément par ElevenLabs : COMMENCE chaque "text" par une phrase d'amorce forte, TERMINE par une phrase de conclusion nette.
- Utilise les points de suspension "..." pour signaler un silence naturel ou une respiration — ElevenLabs les interprète comme pauses.
- Phrases courtes et rythme oral : 15-20 mots maximum par phrase dans les passages denses.
- Aucun markdown, aucun tiret, aucune liste dans les "text".
- Les noms propres (espèces latines, lieux) sont prononcés à voix haute : écris-les phonétiquement si nécessaire ("Turdus merula" → "Turdus merula").
- Mets en valeur les créateurs par leur prénom ou pseudonyme.

━━ FORMAT JSON ━━
{
  "title": "Titre original, poétique, pas générique",
  "description": "Résumé en une phrase",
  "segments": [
    {
      "type": "intro",
      "text": "Ouverture sensorielle forte — image ou ambiance avant tout — puis annonce du fil rouge. 60-90s lues. Commence fort, sans formule générique.",
      "sound_id": null,
      "transition_duration_seconds": null
    },
    {
      "type": "chapter",
      "text": "Chapitre narratif développé : contexte scientifique ou culturel, anecdote de terrain, découverte de la recherche. 90-180s lues. Crescendo interne.",
      "sound_id": null,
      "transition_duration_seconds": null
    },
    {
      "type": "sound_context",
      "text": "Présentation cinématique d'un enregistrement : lieu, heure probable, créateur, indices acoustiques, ce qu'on va entendre et pourquoi ça compte. 45-75s lues.",
      "sound_id": 123,
      "transition_duration_seconds": 30
    },
    {
      "type": "transition",
      "text": "Pivot narratif entre deux paysages ou deux idées. 20-45s lues. Transforme le fil plutôt que de le couper.",
      "sound_id": null,
      "transition_duration_seconds": null
    },
    {
      "type": "outro",
      "text": "Conclusion éditoriale : relie le thème à l'expérience d'écoute, laisse une image mentale durable, invite à continuer l'exploration. 45-75s lues.",
      "sound_id": null,
      "transition_duration_seconds": null
    }
  ],
  "estimated_duration_seconds": 900
}

━━ CONTRAINTES ABSOLUES ━━
- Minimum 4 grands segments narratifs dont au moins 2 segments "chapter" et 3 segments "sound_context" si les sons le permettent.
- Chaque "sound_context" référence un sound_id de la liste fournie.
- "transition_duration_seconds" = durée de fond sonore à jouer après ce segment (en secondes).
- "estimated_duration_seconds" = estimation réaliste de la somme des durées de lecture (à 130 mots/min en français).
- MINIMUM {$minDuration} secondes. MAXIMUM {$maxDuration} secondes.
- Ton : doux, documentaire, incarné. Jamais professoral. Jamais publicitaire.
PROMPT;
    }

    private function validateScriptSchema(array $script): bool
    {
        if (! isset($script['title'], $script['description'], $script['segments']) || ! is_array($script['segments'])) {
            return false;
        }

        if (! isset($script['estimated_duration_seconds']) || ! is_numeric($script['estimated_duration_seconds'])) {
            return false;
        }

        $validTypes = ['intro', 'chapter', 'sound_context', 'transition', 'outro'];

        foreach ($script['segments'] as $segment) {
            if (! is_array($segment)) {
                return false;
            }

            if (! isset($segment['type'], $segment['text']) || ! in_array($segment['type'], $validTypes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Génère un script Flash Info (90-180s, voix pure) depuis un snapshot du site.
     *
     * @param  array<string, mixed>  $context  Sortie de RadioHostContextService::gather()
     * @return array{script: array<string, mixed>, cost_cents: int|null}|null
     */
    public function generateFlashScript(array $context): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('radio.host.ai_model', 'anthropic/claude-opus-4.7');
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('OpenRouter: missing API key for flash generation');

            return null;
        }

        $date = now()->locale('fr')->isoFormat('dddd D MMMM YYYY');
        $time = now()->locale('fr')->isoFormat('HH:mm');
        $window = $this->broadcastWindowLabel(2);
        $brief = $this->dynamicEditorialBrief('flash', $context);
        $newSoundsCount = count($context['new_sounds'] ?? []);
        $newUserNames = $this->listToFrench(array_column($context['new_users'] ?? [], 'name'));
        $popularTitles = array_map(
            fn ($s) => '"'.$s['title'].'" par '.$s['creator'],
            array_slice($context['popular_sounds'] ?? [], 0, 3)
        );
        $topCategories = array_map(fn ($c) => $c['name'], array_slice($context['categories'] ?? [], 0, 4));
        $recentSoundsJson = json_encode(array_slice($context['new_sounds'] ?? [], 0, 5), JSON_UNESCAPED_UNICODE);
        $contextJson = json_encode([
            'total_sounds' => $context['total_sounds'] ?? 0,
            'total_creators' => $context['total_creators'] ?? 0,
            'new_sounds_count' => $newSoundsCount,
            'new_creators' => $newUserNames,
            'popular_sounds' => $popularTitles,
            'top_categories' => $topCategories,
            'recent_sounds' => array_slice($context['new_sounds'] ?? [], 0, 5),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $userPrompt = <<<PROMPT
Prépare un flash info original d'Arborisis Radio.

━━ REPÈRES D'ANTENNE ━━
Date : {$date}
Heure de génération : {$time}
Fenêtre de diffusion visée : {$window}
Moment d'écoute : {$brief['daypart']}
Saison ressentie : {$brief['season']}

Ligne éditoriale de cette génération :
Angle principal : {$brief['angle']}
Ouverture attendue : {$brief['opening']}
Rythme : {$brief['rhythm']}
Image sonore à privilégier : {$brief['sound_image']}
Formulation à éviter : {$brief['avoid']}

Snapshot dynamique de la plateforme :
{$contextJson}

En clair :
- {$context['total_sounds']} sons dans la bibliothèque, {$context['total_creators']} créateurs et créatrices
- Cette semaine : {$newSoundsCount} nouveau(x) son(s)
- Nouveaux créateurs : {$newUserNames}
- Sons tendance : {$this->listToFrench($popularTitles)}
- Catégories dominantes : {$this->listToFrench($topCategories)}

Sons récents (détails) :
{$recentSoundsJson}

━━ INSTRUCTIONS DE RÉDACTION ━━
Rédige un flash radio de 90 à 180 secondes (230 à 450 mots à voix haute, rythme naturel en français).

Structure attendue en 3 à 4 paragraphes distincts :
1. Accroche atmosphérique (2-3 phrases courtes, pas de mention directe de la plateforme, image sensorielle forte, utilise "..." pour signaler une respiration)
2. Actualité de la semaine racontée de façon vivante (nouveautés, créateurs, lieux)
3. Focus sur un son récent — cite son titre, son auteur, ce que ça évoque
4. Invitation à l'écoute : une phrase courte qui donne envie, chaleureuse, sans formule générique

Format JSON strict :
{
  "title": "Titre court, radiophonique, différent à chaque génération",
  "description": "Résumé en une phrase",
  "text": "Le texte complet en un seul bloc, pour stockage",
  "paragraphs": [
    "Paragraphe 1 — accroche (texte brut, sans markdown)",
    "Paragraphe 2 — actualité (texte brut)",
    "Paragraphe 3 — focus son (texte brut)",
    "Paragraphe 4 — invitation (texte brut)"
  ],
  "estimated_duration_seconds": 120,
  "word_count": 300
}

━━ RÈGLES ABSOLUES ━━
- "text" et chaque "paragraphs[n]" : texte brut, jamais de markdown, jamais de tirets, jamais de numérotation
- "paragraphs" est la concaténation de "text" découpée par blocs logiques — utilisé pour la synthèse vocale
- "estimated_duration_seconds" entre 90 et 180
- Transitions fluides entre paragraphes (pas de rupture brusque)
- Langue : français
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Arborisis Radio',
            ])->timeout(180)->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es l\'animateur radio d\'Arborisis Radio, une station dédiée aux sons de la nature et au field recording. Tu parles en français, avec un ton chaleureux, poétique et légèrement journalistique. Tu donnes chaque flash info comme une capsule vivante. Tu ne fais jamais de liste monotone : tu racontes. Réponds UNIQUEMENT en JSON strict sans balise de code.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.8,
                'max_tokens' => 4096,
            ]);

            if (! $response->successful()) {
                Log::warning('OpenRouter: flash script generation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                return null;
            }

            $script = json_decode($content, true);

            if (! is_array($script) || empty($script['text']) || mb_strlen($script['text']) < 100) {
                Log::warning('OpenRouter: invalid flash script schema', ['content' => $content]);

                return null;
            }

            // Valeur par défaut si le modèle oublie le champ
            if (empty($script['estimated_duration_seconds'])) {
                $wordCount = str_word_count($script['text']);
                $script['estimated_duration_seconds'] = (int) round($wordCount / 2.5);
            }

            // Reconstruit paragraphs depuis text si le modèle l'a oublié
            if (empty($script['paragraphs']) || ! is_array($script['paragraphs'])) {
                $script['paragraphs'] = array_values(array_filter(
                    array_map('trim', preg_split('/\n{2,}/', $script['text']) ?: []),
                    fn ($p) => $p !== ''
                ));
            }

            $cost = $data['usage']['total_cost'] ?? null;

            Log::info('OpenRouter: flash script generated', [
                'model' => $model,
                'estimated_duration' => $script['estimated_duration_seconds'],
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ]);

            return [
                'script' => $script,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenRouter: exception during flash generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Génère un script d'émission complète (8-15 min) avec segments séquentiels.
     *
     * @param  list<Sound>  $sounds  Sons sélectionnés (3-5)
     * @param  array<string, mixed>  $context  Sortie de RadioHostContextService::gather()
     * @return array{script: array<string, mixed>, cost_cents: int|null}|null
     */
    public function generateEmissionScript(array $sounds, array $context): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('radio.host.ai_model', 'anthropic/claude-opus-4.7');
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('OpenRouter: missing API key for emission generation');

            return null;
        }

        $date = now()->locale('fr')->isoFormat('dddd D MMMM YYYY');
        $time = now()->locale('fr')->isoFormat('HH:mm');
        $brief = $this->dynamicEditorialBrief('emission', $context);
        $topCategories = array_map(fn ($c) => $c['name'].' ('.$c['count'].' sons)', array_slice($context['categories'] ?? [], 0, 5));

        $soundsData = [];
        foreach ($sounds as $sound) {
            $soundsData[] = $this->soundDataForScript($sound);
        }

        $soundsJson = json_encode($soundsData, JSON_UNESCAPED_UNICODE);
        $categoriesStr = $this->listToFrench($topCategories);

        $userPrompt = <<<PROMPT
Prépare une émission complète d'Arborisis Radio de 8 à 15 minutes.

━━ REPÈRES D'ANTENNE ━━
Date : {$date}
Heure de génération : {$time}
Moment d'écoute : {$brief['daypart']}
Saison ressentie : {$brief['season']}
Angle de cette émission : {$brief['angle']}
Style d'ouverture : {$brief['opening']}
Rythme narratif : {$brief['rhythm']}
Image sonore dominante : {$brief['sound_image']}
À éviter cette fois : {$brief['avoid']}

━━ CONTEXTE PLATEFORME ━━
{$context['total_sounds']} sons dans la bibliothèque, {$context['total_creators']} créateurs et créatrices.
Catégories dominantes : {$categoriesStr}

━━ SONS DE L'ÉMISSION (DANS CET ORDRE) ━━
{$soundsJson}

Chaque son peut contenir un champ "analysis" issu du pipeline Arborisis :
- "main_detected_species" : espèces probables (BirdNET) avec plages de temps et fréquence → cite-les comme détections probables, jamais comme certitudes. Utilise le nom commun français, le nom latin en parenthèses si distinctif.
- "acoustic_profile" : spectral_centroid (clarté/brillance), event_density (densité d'événements), harmonic_ratio (richesse harmonique), dynamic_range_db (contraste sonore) → traduis ces chiffres en sensations auditives (ex. : "grave et dense", "nombreux événements brefs", "paysage dominé par des fréquences aiguës").
- "quality" : loudness_lufs, noise_floor_db → mentionne la qualité d'enregistrement si elle est remarquable (ex. un fond très silencieux = lieu isolé).

━━ INSTRUCTIONS D'ÉCRITURE POUR LA VOIX ━━
- Chaque "text" est synthétisé SÉPARÉMENT par ElevenLabs : commence et termine chaque segment par une phrase complète et autosuffisante.
- Utilise "..." pour signaler une respiration ou une pause d'écoute naturelle.
- Phrases courtes (15-20 mots max dans les passages denses). Pas de markdown, pas de liste.
- sound_intro : crée l'attente — lieu, ambiance pressentie, qui a enregistré et pourquoi ça compte. Exploite les données d'analyse pour l'image acoustique.
- sound_outro : après l'écoute — ce qu'on vient d'entendre, ce que ça évoque, un lien avec le suivant ou avec le thème de l'émission.

━━ FORMAT SÉQUENTIEL OBLIGATOIRE ━━
1. intro : accueil chaleureux, image sensorielle forte, annonce du voyagé (30-45s)
2. Pour chaque son dans l'ordre :
   a. sound_intro : mise en attente du son (15-30s)
   b. sound : diffusion intégrale du son (duration_seconds de l'enregistrement)
   c. sound_outro : réflexion post-écoute + transition vers le suivant (20-35s)
3. outro : clôture poétique et invitation à revenir (25-40s)

Structure JSON exacte :
{
  "title": "Titre original, lié aux lieux ou espèces des sons sélectionnés",
  "description": "Résumé en une phrase",
  "estimated_duration_seconds": 600,
  "segments": [
    {"type": "intro", "text": "...", "duration_estimate": 40},
    {"type": "sound_intro", "sound_id": 42, "text": "...", "duration_estimate": 25},
    {"type": "sound", "sound_id": 42, "duration_seconds": 90},
    {"type": "sound_outro", "sound_id": 42, "text": "...", "duration_estimate": 25},
    {"type": "outro", "text": "...", "duration_estimate": 30}
  ]
}

━━ CONTRAINTES ABSOLUES ━━
- "text" : texte brut, jamais de markdown, jamais de tirets, jamais de liste
- Les "sound_id" doivent correspondre EXACTEMENT aux IDs fournis
- Chaque son : exactement un sound_intro, un sound, un sound_outro
- "estimated_duration_seconds" entre 480 et 900
- Langue : français
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Arborisis Radio',
            ])->timeout(180)->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es l\'animateur vedette d\'Arborisis Radio, une station nature et field recording. Tu conçois des émissions radio complètes en français : ton ton est chaleureux, documentaire, poétique. Tu guides l\'auditeur comme un naturaliste qui sait raconter ce qu\'il entend — pas seulement identifier. Tu exploites les données d\'analyse acoustique (BirdNET, profil spectral, LUFS) pour enrichir ta narration, sans jamais les citer comme des faits absolus. Chaque segment de voice sera synthétisé séparément : rends chaque "text" autonome et complet. Tu ne fais jamais de liste, jamais de markdown. Réponds UNIQUEMENT en JSON strict sans balise de code.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.75,
                'max_tokens' => 8192,
            ]);

            if (! $response->successful()) {
                Log::warning('OpenRouter: emission script generation failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                return null;
            }

            $script = json_decode($content, true);

            if (! is_array($script) || ! $this->validateEmissionSchema($script)) {
                Log::warning('OpenRouter: invalid emission script schema', ['content' => $content]);

                return null;
            }

            $cost = $data['usage']['total_cost'] ?? null;

            Log::info('OpenRouter: emission script generated', [
                'model' => $model,
                'segments' => count($script['segments']),
                'estimated_duration' => $script['estimated_duration_seconds'],
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ]);

            return [
                'script' => $script,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenRouter: exception during emission generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function validateEmissionSchema(array $script): bool
    {
        if (! isset($script['title'], $script['description'], $script['segments']) || ! is_array($script['segments'])) {
            return false;
        }

        if (! isset($script['estimated_duration_seconds']) || ! is_numeric($script['estimated_duration_seconds'])) {
            return false;
        }

        $validTypes = ['intro', 'sound_intro', 'sound', 'sound_outro', 'outro'];

        foreach ($script['segments'] as $segment) {
            if (! is_array($segment) || ! isset($segment['type']) || ! in_array($segment['type'], $validTypes, true)) {
                return false;
            }

            if ($segment['type'] === 'sound') {
                if (! isset($segment['sound_id'], $segment['duration_seconds'])) {
                    return false;
                }
            } else {
                if (empty($segment['text'])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>|null
     */
    public function generateRadioProductionPlan(array $metadata): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('radio.production.ai_model', config('radio.host.ai_model', 'anthropic/claude-opus-4.7'));
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            return null;
        }

        $payload = json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $prompt = <<<PROMPT
Tu es le réalisateur sonore d'Arborisis Radio. Tu décides du montage audio final d'un contenu nature : musique Eleven Music, FX ElevenLabs, ambiance, niveaux et intention de mixage.

Contexte brut du contenu :
{$payload}

Notes de réalisation :
- Si "show_type" est "podcast" ou "flash" : la voix est une narration continue à espacer légèrement derrière un lit musical discret. La musique doit presque disparaître pendant les passages denses.
- Si "show_type" est "emission" : l'émission alterne voix et sons de terrain — le lit musical doit être encore plus discret pour ne pas rentrer en concurrence avec les enregistrements originaux.
- L'intro_fx et l'outro_fx sont de courtes signatures sonores organiques (5 secondes), pas des jingles commerciaux.
- Préfère des textures évolutives et des ambiances spatialisées — pas de boucles répétitives ni de percussions.

Retourne UNIQUEMENT ce JSON strict :
{
  "creative_direction": "Une phrase précise sur l'intention sonore — quel espace, quelle émotion, quel arc temporel",
  "music_prompt": "Prompt Eleven Music en anglais, instrumental, précis (textures, instruments, feeling), sans marque ni artiste connu, laisse de l'espace pour voix française",
  "ambient_prompt": "Prompt ElevenLabs sound effects en anglais pour un fond d'ambiance naturel bouclable, spatial, aérien",
  "intro_fx_prompt": "Prompt ElevenLabs sound effects en anglais : ouverture organique 5 secondes, nature, aucune voix, aucun instrument commercial",
  "outro_fx_prompt": "Prompt ElevenLabs sound effects en anglais : fermeture organique 5 secondes, résolution douce, aucune voix",
  "music_duration_seconds": 60,
  "music_volume": 0.12,
  "field_bed_volume": 0.16,
  "ident_volume": 0.22
}

Contraintes :
- Tous les prompts en anglais, utilisables directement par ElevenLabs.
- Aucun prompt ne cite de voix, de paroles, de marque, d'artiste ou d'œuvre connue.
- music_volume entre 0.06 et 0.16 (au-delà, ça couvre la narration).
- field_bed_volume entre 0.08 et 0.20.
- ident_volume entre 0.14 et 0.28.
- music_duration_seconds entre 10 et 90.
- Le résultat doit sonner premium, calme, naturaliste. Jamais agressif, jamais commercial.
PROMPT;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => 'Arborisis Radio',
            ])->timeout(90)->post("{$baseUrl}/chat/completions", [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un réalisateur sonore senior pour une radio premium de field recording. Tu réponds uniquement en JSON strict. Tu prends toutes les décisions de sound design et de mixage.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.65,
                'max_tokens' => 2048,
            ]);

            if (! $response->successful()) {
                Log::warning('OpenRouter: production plan generation failed', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $content = $response->json('choices.0.message.content');
            if (! is_string($content)) {
                return null;
            }

            $plan = json_decode($content, true);
            if (! is_array($plan) || ! $this->validateProductionPlan($plan)) {
                Log::warning('OpenRouter: invalid production plan schema', ['content' => $content]);

                return null;
            }

            Log::info('OpenRouter: radio production plan generated', [
                'model' => $model,
                'direction' => $plan['creative_direction'] ?? null,
            ]);

            return $this->normalizeProductionPlan($plan);
        } catch (\Throwable $e) {
            Log::error('OpenRouter: exception during production plan generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Generates one short DJ intro using a personality-driven prompt pair.
     *
     * @param  array{system: string, user: string}  $prompts
     * @return array{text: string, opening: string, mentions?: array<string, mixed>, model: string, usage?: array<string, mixed>, cost_cents?: int|null}|null
     */
    public function generateDjScript(array $prompts, float $temperature = 0.85, int $maxTokens = 320): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('radio.host.ai_model', self::DEFAULT_MODEL);
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('OpenRouter: missing API key for DJ script generation');

            return null;
        }

        try {
            $response = $this->postChatCompletion($apiKey, $baseUrl, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $prompts['system']],
                    ['role' => 'user', 'content' => $prompts['user']],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens,
            ], timeoutSeconds: 60);

            if (! $response->successful()) {
                Log::warning('OpenRouter: DJ script generation failed', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return null;
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                return null;
            }

            $decoded = json_decode($content, true);
            if (! is_array($decoded) || empty($decoded['text']) || ! is_string($decoded['text'])) {
                Log::warning('OpenRouter: DJ script invalid JSON shape', [
                    'content' => mb_substr($content, 0, 400),
                ]);

                return null;
            }

            $text = trim((string) $decoded['text']);
            $opening = trim((string) ($decoded['opening'] ?? ''));
            $mentions = is_array($decoded['mentions'] ?? null) ? $decoded['mentions'] : [];

            $cost = $data['usage']['total_cost'] ?? null;

            return [
                'text' => $text,
                'opening' => $opening,
                'mentions' => $mentions,
                'model' => $model,
                'usage' => $data['usage'] ?? [],
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ];
        } catch (\Throwable $e) {
            Log::error('OpenRouter: exception during DJ script generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function validateProductionPlan(array $plan): bool
    {
        foreach (['music_prompt', 'ambient_prompt', 'intro_fx_prompt', 'outro_fx_prompt'] as $key) {
            if (empty($plan[$key]) || ! is_string($plan[$key])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function postChatCompletion(string $apiKey, string $baseUrl, array $payload, int $timeoutSeconds = 300): Response
    {
        $request = fn (array $body) => Http::withHeaders([
            'Authorization' => 'Bearer '.$apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url'),
            'X-Title' => 'Arborisis Radio',
        ])->timeout($timeoutSeconds)->post("{$baseUrl}/chat/completions", $body);

        $openFallback = fn () => throw new \RuntimeException('OpenRouter circuit breaker is open');
        $response = app(CircuitBreaker::class)->attempt('openrouter', fn () => $request($payload), $openFallback);

        if ($response->status() === 400) {
            $body = $response->body();
            if (isset($payload['plugins']) && str_contains($body, 'plugins')) {
                Log::warning('OpenRouter: web search plugin rejected, retrying without it', ['body' => $body]);
                unset($payload['plugins']);

                return app(CircuitBreaker::class)->attempt('openrouter', fn () => $request($payload), $openFallback);
            }
            if (isset($payload['tools']) && str_contains($body, 'tools')) {
                Log::warning('OpenRouter: tools rejected by model, retrying without web search', ['body' => $body]);
                unset($payload['tools']);

                return app(CircuitBreaker::class)->attempt('openrouter', fn () => $request($payload), $openFallback);
            }
        }

        return $response;
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return array<string, mixed>
     */
    private function normalizeProductionPlan(array $plan): array
    {
        $plan['music_duration_seconds'] = max(10, min(90, (int) ($plan['music_duration_seconds'] ?? 60)));
        $plan['music_volume'] = max(0.06, min(0.18, (float) ($plan['music_volume'] ?? 0.12)));
        $plan['field_bed_volume'] = max(0.08, min(0.22, (float) ($plan['field_bed_volume'] ?? 0.16)));
        $plan['ident_volume'] = max(0.12, min(0.30, (float) ($plan['ident_volume'] ?? 0.22)));

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, string>
     */
    private function dynamicEditorialBrief(string $format, array $context = []): array
    {
        $popularSound = $context['popular_sounds'][0]['title'] ?? null;
        $recentSound = $context['new_sounds'][0]['title'] ?? null;
        $category = $context['categories'][0]['name'] ?? null;

        $formatAngles = [
            'flash' => [
                'partir des nouveautés de la semaine et les relier à une ambiance d’écoute immédiate',
                'mettre en avant un détail sonore concret avant les chiffres de la plateforme',
                'raconter la bibliothèque comme un carnet de terrain qui vient de s’enrichir',
                'ouvrir sur ce que les sons récents révèlent des lieux enregistrés',
            ],
            'podcast' => [
                'construire une progression intime, presque documentaire, autour du thème',
                'relier les sons à une question d’écologie sonore contemporaine',
                'faire entendre les détails minuscules avant d’élargir vers le paysage',
                'donner une lecture sensible des analyses acoustiques sans ton professoral',
            ],
            'emission' => [
                'organiser l’écoute comme une traversée de paysages successifs',
                'faire dialoguer les créateurs, les lieux et les indices acoustiques',
                'alterner observation naturaliste prudente et récit radiophonique',
                'laisser chaque son imposer son tempo narratif',
            ],
        ];

        $angles = $formatAngles[$format] ?? $formatAngles['podcast'];

        if ($popularSound) {
            $angles[] = 'prendre "'.$popularSound.'" comme point d’entrée, sans répéter le titre en boucle';
        }

        if ($recentSound) {
            $angles[] = 'faire de "'.$recentSound.'" le fil rouge discret de la narration';
        }

        if ($category) {
            $angles[] = 'observer la couleur sonore dominante autour de la catégorie '.$category;
        }

        return [
            'daypart' => $this->daypartLabel(),
            'season' => $this->seasonLabel(),
            'angle' => $this->pick($angles),
            'opening' => $this->pick([
                'commencer par une sensation d’espace avant de nommer la plateforme',
                'commencer par une image auditive courte, puis glisser vers les faits',
                'commencer comme une prise de son sur le terrain, sans formule générique',
                'commencer par un contraste entre silence, détail et paysage',
            ]),
            'rhythm' => $this->pick([
                'phrases courtes au début, puis respiration plus ample',
                'ton fluide, précis, sans accumulation de données',
                'rythme calme avec une montée légère vers l’invitation finale',
                'narration vivante, moins bulletin administratif que chronique sonore',
            ]),
            'sound_image' => $this->pick([
                'lisière, souffle, feuillage, distance',
                'matière du sol, air humide, présence animale probable',
                'profondeur du lieu, petits signaux, arrière-plan vivant',
                'grain du micro, relief acoustique, silence autour des sons',
            ]),
            'avoid' => $this->pick([
                'ne pas reprendre les mêmes tournures que les générations précédentes',
                'ne pas ouvrir par "Aujourd’hui sur Arborisis Radio"',
                'ne pas transformer les statistiques en liste',
                'ne pas affirmer les espèces détectées comme des certitudes',
            ]),
        ];
    }

    private function broadcastWindowLabel(int $hours): string
    {
        $start = now();
        $end = now()->addHours($hours);

        return $start->locale('fr')->isoFormat('HH:mm').' - '.$end->locale('fr')->isoFormat('HH:mm');
    }

    private function daypartLabel(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 5 => 'nuit profonde',
            $hour < 8 => 'aube',
            $hour < 12 => 'matin',
            $hour < 14 => 'milieu de journée',
            $hour < 18 => 'après-midi',
            $hour < 22 => 'soirée',
            default => 'nuit',
        };
    }

    private function seasonLabel(): string
    {
        $month = (int) now()->format('n');

        return match (true) {
            in_array($month, [3, 4, 5], true) => 'printemps',
            in_array($month, [6, 7, 8], true) => 'été',
            in_array($month, [9, 10, 11], true) => 'automne',
            default => 'hiver',
        };
    }

    /** @param list<string> $items */
    private function pick(array $items): string
    {
        return $items[array_rand($items)];
    }

    /** @return array<string, mixed> */
    private function soundDataForScript(object $sound): array
    {
        $analysis = method_exists($sound, 'relationLoaded') && $sound->relationLoaded('soundAnalysis')
            ? $sound->soundAnalysis
            : null;
        $detections = $analysis && method_exists($analysis, 'relationLoaded') && $analysis->relationLoaded('birdnetDetections')
            ? $analysis->birdnetDetections
            : collect();
        $features = $analysis?->features_json ?? [];

        return [
            'id' => $sound->id,
            'title' => $sound->title,
            'creator' => $sound->user?->name ?? 'Arborisis',
            'description' => $sound->description,
            'duration_seconds' => $sound->duration,
            'location' => $sound->soundLocation?->location_name,
            'category' => $sound->category?->name,
            'environment' => $sound->environment?->name ?? null,
            'play_count' => $sound->play_count ?? 0,
            'like_count' => $sound->like_count ?? 0,
            'analysis' => $analysis ? [
                'main_detected_species' => $detections
                    ->sortByDesc('confidence')
                    ->unique('common_name')
                    ->take(5)
                    ->map(fn ($detection) => [
                        'name' => $detection->common_name,
                        'scientific_name' => $detection->scientific_name,
                        'confidence' => (float) $detection->confidence,
                        'time_range_seconds' => [
                            'start' => (float) $detection->start_time,
                            'end' => (float) $detection->end_time,
                        ],
                        'frequency_range_hz' => [
                            'min' => $detection->frequency_min !== null ? (float) $detection->frequency_min : null,
                            'max' => $detection->frequency_max !== null ? (float) $detection->frequency_max : null,
                        ],
                    ])
                    ->values()
                    ->all(),
                'acoustic_profile' => [
                    'spectral_centroid_hz' => $analysis->spectral_centroid ?? ($features['spectral_centroid'] ?? null),
                    'spectral_rolloff_hz' => $analysis->spectral_rolloff ?? ($features['spectral_rolloff'] ?? null),
                    'zero_crossing_rate' => $analysis->zero_crossing_rate ?? ($features['zero_crossing_rate'] ?? null),
                    'event_density' => $features['event_density'] ?? null,
                    'tempo_bpm' => $features['tempo_bpm'] ?? null,
                    'harmonic_ratio' => $features['harmonic_ratio'] ?? null,
                    'dynamic_range_db' => $features['dynamic_range_db'] ?? null,
                ],
                'quality' => [
                    'label' => $analysis->quality_label,
                    'noise_floor_db' => $analysis->noise_floor_db,
                    'loudness_lufs' => $analysis->loudness_lufs,
                    'usable_for_analysis' => $analysis->quality_json['usable_for_analysis'] ?? null,
                ],
                'spectrogram_available' => $analysis->spectrogram_r2_key !== null,
            ] : null,
        ];
    }

    /** @param list<string> $items */
    private function listToFrench(array $items): string
    {
        if (empty($items)) {
            return 'aucun';
        }

        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items).' et '.$last;
    }
}
