<?php

declare(strict_types=1);

namespace App\Services\SoundIdeas;

use App\Models\Category;
use App\Models\DailySoundIdea;
use App\Models\Sound;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Log;

class AiSoundIdeaGeneratorService
{
    private const DEFAULT_MODEL = 'anthropic/claude-opus-4.7';

    private const BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * @return array{ideas: list<array<string, mixed>>, theme: string|null}
     */
    public function generateDailyIdeas(): array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('services.openrouter.model', self::DEFAULT_MODEL);
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('AiSoundIdeaGenerator: missing OpenRouter API key');

            return ['ideas' => [], 'theme' => null];
        }

        $context = $this->buildSiteContext();
        $prompt = $this->buildPrompt($context);

        try {
            $response = app(OpenRouterService::class)->postChatCompletion($apiKey, $baseUrl, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un sound designer naturaliste et un conseiller créatif pour Arborisis, une plateforme de field recording dédiée aux sons de la nature. Tu suggères des idées d\'enregistrement sonore originales, poétiques et réalisables. Ton ton est doux, inspirant, respectueux de la nature. Tu réponds UNIQUEMENT en JSON strict sans balise de code.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.85,
                'max_tokens' => 4096,
            ], timeoutSeconds: 120);

            if (! $response->successful()) {
                Log::warning('AiSoundIdeaGenerator: generation failed', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return ['ideas' => [], 'theme' => null];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                Log::warning('AiSoundIdeaGenerator: empty content');

                return ['ideas' => [], 'theme' => null];
            }

            $result = json_decode($content, true);

            if (! is_array($result) || empty($result['ideas']) || ! is_array($result['ideas'])) {
                Log::warning('AiSoundIdeaGenerator: invalid JSON schema', ['content' => mb_substr($content, 0, 800)]);

                return ['ideas' => [], 'theme' => null];
            }

            $ideas = [];
            foreach ($result['ideas'] as $idea) {
                $validated = $this->validateIdeaPayload($idea);
                if ($validated === null) {
                    continue;
                }
                $ideas[] = $validated;
            }

            $theme = is_string($result['theme_of_the_day'] ?? null) ? $result['theme_of_the_day'] : null;
            $cost = $data['usage']['total_cost'] ?? null;

            Log::info('AiSoundIdeaGenerator: daily ideas generated', [
                'count' => count($ideas),
                'model' => $model,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ]);

            return [
                'ideas' => $ideas,
                'theme' => $theme,
            ];
        } catch (\Throwable $e) {
            Log::error('AiSoundIdeaGenerator: exception during generation', [
                'exception' => $e->getMessage(),
            ]);

            return ['ideas' => [], 'theme' => null];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSiteContext(): array
    {
        $today = now();

        $totalSounds = Sound::count();
        $topCategories = Category::withCount('sounds')
            ->orderByDesc('sounds_count')
            ->limit(5)
            ->get()
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'count' => $c->sounds_count,
            ])
            ->toArray();

        $recentSounds = Sound::with('category')
            ->whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn (Sound $s) => [
                'title' => $s->title,
                'category' => $s->category?->name ?? 'Inconnu',
            ])
            ->toArray();

        $recentIdeas = DailySoundIdea::whereDate('date', '>=', $today->copy()->subDays(14))
            ->orderByDesc('date')
            ->limit(20)
            ->pluck('title')
            ->toArray();

        return [
            'date' => $today->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'season' => $this->seasonLabel($today),
            'total_sounds' => $totalSounds,
            'top_categories' => $topCategories,
            'recent_sounds' => $recentSounds,
            'recent_ideas' => $recentIdeas,
        ];
    }

    private function seasonLabel(\Carbon\Carbon $date): string
    {
        $month = (int) $date->format('n');

        return match (true) {
            in_array($month, [3, 4, 5]) => 'printemps',
            in_array($month, [6, 7, 8]) => 'été',
            in_array($month, [9, 10, 11]) => 'automne',
            default => 'hiver',
        };
    }

    private function buildPrompt(array $context): string
    {
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return <<<PROMPT
Tu dois générer entre 4 et 6 idées d'enregistrement sonore originales pour la plateforme Arborisis — une communauté de field recording et d'écologie sonore.

Chaque idée doit être une suggestion concrète de son à capturer dans la nature, avec un angle créatif et poétique.

━━ CONTEXTE DU JOUR ━━
{$contextJson}

━━ CONTRAINTES ABSOLUES ━━
- Génère entre 4 et 6 idées (ni plus, ni moins).
- Chaque idée doit être réalisable en UNE SEULE SORTIE de field recording (30 min à 2h).
- Pas de dark patterns, pas de pression. Ton est doux, poétique, naturaliste.
- Les titres doivent être évocateurs et poétiques (ex: "Le souffle de l'aube sous les frênes").
- Les descriptions doivent raconter une invitation à l'écoute, pas une instruction robotique.
- difficulty : "easy" (accessible à tous), "medium" (nécessite un peu de patience), "hard" (condition rare ou technique).
- Répartition : 50% easy, 35% medium, 15% hard.
- tags : 2 à 4 mots-clés techniques ou thématiques (ex: ["pluie", "feuillage", "macro"]).
- season_context : la saison actuelle en français.
- time_of_day : "matin", "midi", "après-midi", "soir", "nuit", ou "toute la journée".
- AUCUNE répétition avec les idées des 14 derniers jours listées ci-dessus.
- Varie les ambiances : eau, vent, forêt, urbain naturel, faune, météo, minéral, etc.

━━ FORMAT JSON STRICT ━━
{
  "ideas": [
    {
      "title": "Titre poétique et évocateur (max 80 caractères)",
      "description": "Description engageante qui invite à l'exploration sonore (2-3 phrases). Mentionne l'ambiance, le lieu suggéré, la technique d'écoute.",
      "difficulty": "easy",
      "tags": ["forêt", "vent", "feuillage"],
      "season_context": "printemps",
      "weather_context": "Après une pluie fine",
      "time_of_day": "matin"
    }
  ],
  "theme_of_the_day": "Une courte phrase poétique qui relie les idées du jour (max 100 caractères)"
}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $idea
     * @return array<string, mixed>|null
     */
    private function validateIdeaPayload(array $idea): ?array
    {
        if (empty($idea['title']) || ! is_string($idea['title']) || mb_strlen($idea['title']) > 120) {
            return null;
        }

        if (empty($idea['description']) || ! is_string($idea['description'])) {
            return null;
        }

        $difficulty = $idea['difficulty'] ?? 'easy';
        if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'easy';
        }

        $tags = [];
        if (! empty($idea['tags']) && is_array($idea['tags'])) {
            $tags = array_values(array_filter($idea['tags'], 'is_string'));
            $tags = array_slice($tags, 0, 6);
        }

        return [
            'title' => mb_substr(trim($idea['title']), 0, 120),
            'description' => trim($idea['description']),
            'difficulty' => $difficulty,
            'tags' => $tags,
            'season_context' => is_string($idea['season_context'] ?? null) ? mb_substr(trim($idea['season_context']), 0, 30) : null,
            'weather_context' => is_string($idea['weather_context'] ?? null) ? mb_substr(trim($idea['weather_context']), 0, 40) : null,
            'time_of_day' => is_string($idea['time_of_day'] ?? null) ? mb_substr(trim($idea['time_of_day']), 0, 20) : null,
        ];
    }
}
