<?php

declare(strict_types=1);

namespace App\Services\Gamification;

use App\Enums\ObjectiveType;
use App\Enums\QuestType;
use App\Models\ArborisisPoint;
use App\Models\ArborisisVisit;
use App\Models\Category;
use App\Models\Quest;
use App\Models\Sound;
use App\Models\User;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Log;

class AiQuestGeneratorService
{
    private const DEFAULT_MODEL = 'anthropic/claude-opus-4.7';

    private const BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * @return array{quests: list<array<string, mixed>>, theme: string|null}
     */
    public function generateDailyQuests(): array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('services.openrouter.model', self::DEFAULT_MODEL);
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('AiQuestGenerator: missing OpenRouter API key');

            return ['quests' => [], 'theme' => null];
        }

        $context = $this->buildSiteContext();
        $prompt = $this->buildPrompt($context);

        try {
            $response = app(OpenRouterService::class)->postChatCompletion($apiKey, $baseUrl, [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es le concepteur de quêtes quotidiennes d\'Arborisis, une plateforme sociale premium de field recording dédiée aux sons de la nature. Tu crées des défis poétiques, respectueux de la nature, qui encouragent l\'exploration sonore sans dark patterns ni FOMO. Ton ton est doux, inspirant, naturaliste. Tu réponds UNIQUEMENT en JSON strict sans balise de code.',
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
                Log::warning('AiQuestGenerator: generation failed', [
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 500),
                ]);

                return ['quests' => [], 'theme' => null];
            }

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'] ?? null;

            if (! is_string($content)) {
                Log::warning('AiQuestGenerator: empty content');

                return ['quests' => [], 'theme' => null];
            }

            $result = json_decode($content, true);

            if (! is_array($result) || empty($result['quests']) || ! is_array($result['quests'])) {
                Log::warning('AiQuestGenerator: invalid JSON schema', ['content' => mb_substr($content, 0, 800)]);

                return ['quests' => [], 'theme' => null];
            }

            $quests = [];
            foreach ($result['quests'] as $q) {
                $validated = $this->validateQuestPayload($q);
                if ($validated === null) {
                    continue;
                }
                $quests[] = $validated;
            }

            $theme = is_string($result['theme_of_the_day'] ?? null) ? $result['theme_of_the_day'] : null;

            $cost = $data['usage']['total_cost'] ?? null;

            Log::info('AiQuestGenerator: daily quests generated', [
                'count' => count($quests),
                'model' => $model,
                'cost_cents' => $cost !== null ? (int) round($cost * 100) : null,
            ]);

            return [
                'quests' => $quests,
                'theme' => $theme,
            ];
        } catch (\Throwable $e) {
            Log::error('AiQuestGenerator: exception during generation', [
                'exception' => $e->getMessage(),
            ]);

            return ['quests' => [], 'theme' => null];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSiteContext(): array
    {
        $today = now();
        $yesterday = $today->copy()->subDay();
        $lastWeek = $today->copy()->subDays(7);

        $totalSounds = Sound::count();
        $totalCreators = User::whereHas('sounds')->count();
        $totalPoints = ArborisisPoint::where('moderation_status', 'approved')->count();
        $totalVisitsYesterday = ArborisisVisit::whereDate('visited_at', $yesterday)->count();

        $recentSounds = Sound::with('category')
            ->whereDate('created_at', '>=', $lastWeek)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Sound $s) => [
                'title' => $s->title,
                'category' => $s->category?->name ?? 'Inconnu',
                'duration' => $s->duration,
            ])
            ->toArray();

        $topCategories = Category::withCount('sounds')
            ->orderByDesc('sounds_count')
            ->limit(5)
            ->get()
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'count' => $c->sounds_count,
            ])
            ->toArray();

        $recentPoints = ArborisisPoint::where('moderation_status', 'approved')
            ->whereDate('approved_at', '>=', $lastWeek)
            ->latest('approved_at')
            ->limit(5)
            ->get()
            ->map(fn (ArborisisPoint $p) => [
                'title' => $p->title,
                'category' => $p->category?->value ?? 'autre',
            ])
            ->toArray();

        $recentQuests = Quest::where('type', QuestType::Daily)
            ->whereDate('starts_at', '>=', $lastWeek)
            ->orderByDesc('starts_at')
            ->limit(14)
            ->get()
            ->map(fn (Quest $q) => [
                'title' => $q->title,
                'objective_type' => $q->objective_type?->value ?? $q->objective_type,
                'objective_target' => $q->objective_target,
            ])
            ->toArray();

        $objectiveTypes = [];
        foreach (ObjectiveType::cases() as $case) {
            $objectiveTypes[] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return [
            'date' => $today->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'season' => $this->seasonLabel($today),
            'total_sounds' => $totalSounds,
            'total_creators' => $totalCreators,
            'total_points' => $totalPoints,
            'visits_yesterday' => $totalVisitsYesterday,
            'recent_sounds' => $recentSounds,
            'top_categories' => $topCategories,
            'recent_points' => $recentPoints,
            'recent_quests' => $recentQuests,
            'objective_types' => $objectiveTypes,
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

        $objectiveValues = implode(', ', array_map(fn (ObjectiveType $o) => $o->value, ObjectiveType::cases()));

        return <<<PROMPT
Tu dois générer entre 2 et 4 quêtes quotidiennes originales pour la plateforme Arborisis — une communauté de field recording et d'écologie sonore.

━━ CONTEXTE DU JOUR ━━
{$contextJson}

━━ CONTRAINTES ABSOLUES ━━
- Génère entre 2 et 4 quêtes daily (ni plus, ni moins).
- Chaque quête doit être réalisable en UNE SEULE JOURNÉE par un utilisateur lambda.
- Pas de dark patterns, pas de FOMO, pas de pression. Ton est doux, poétique, naturaliste.
- Les titres doivent être évocateurs et poétiques (pas "Visiter 3 points" mais "Trois pas dans l'aube").
- Les descriptions doivent raconter une invitation à l'exploration, pas une instruction robotique.
- objectif_target : toujours un entier positif. Pour les quêtes faciles : 1-2. Moyennes : 3-5. Difficiles : 5-10 (rare).
- reward_xp : proportionnel à la difficulté. Facile : 10-15. Moyen : 20-35. Difficile : 40-60.
- objective_type doit être UNIQUEMENT parmi : {$objectiveValues}
- objective_payload : objet JSON optionnel pour affiner la quête (ex: {"category":"forest"}). Peut être null ou {}.
- Difficulté : 80% easy, 20% medium. Jamais hard pour les quêtes daily.
- AUCUNE répétition avec les quêtes des 7 derniers jours listées ci-dessus.
- Variété : si hier c'était des quêtes de visite, aujourd'hui privilégie écoute, création ou commentaire.

━━ FORMAT JSON STRICT ━━
{
  "quests": [
    {
      "title": "Titre poétique et évocateur (max 60 caractères)",
      "description": "Description engageante, douce, invitant à l'exploration sonore (1-2 phrases)",
      "objective_type": "visit_points",
      "objective_target": 2,
      "objective_payload": {},
      "reward_xp": 25,
      "difficulty": "easy"
    }
  ],
  "theme_of_the_day": "Une courte phrase poétique qui relie les quêtes du jour (max 100 caractères)"
}
PROMPT;
    }

    /**
     * @param  array<string, mixed>  $q
     * @return array<string, mixed>|null
     */
    private function validateQuestPayload(array $q): ?array
    {
        if (empty($q['title']) || ! is_string($q['title']) || mb_strlen($q['title']) > 120) {
            return null;
        }

        if (empty($q['description']) || ! is_string($q['description'])) {
            return null;
        }

        $objectiveType = $q['objective_type'] ?? null;
        if (! is_string($objectiveType) || ObjectiveType::tryFrom($objectiveType) === null) {
            return null;
        }

        $target = (int) ($q['objective_target'] ?? 0);
        if ($target < 1 || $target > 50) {
            return null;
        }

        $xp = (int) ($q['reward_xp'] ?? 0);
        if ($xp < 1 || $xp > 500) {
            return null;
        }

        $payload = [];
        if (! empty($q['objective_payload']) && is_array($q['objective_payload'])) {
            $payload = $q['objective_payload'];
        }

        return [
            'title' => mb_substr(trim($q['title']), 0, 120),
            'description' => trim($q['description']),
            'objective_type' => $objectiveType,
            'objective_target' => $target,
            'objective_payload' => $payload,
            'reward_xp' => $xp,
            'category' => 'ai_daily',
        ];
    }
}
