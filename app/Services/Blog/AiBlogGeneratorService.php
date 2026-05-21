<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Models\Sound;
use App\Models\User;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiBlogGeneratorService
{
    private const DEFAULT_MODEL = 'anthropic/claude-opus-4.7';
    private const BASE_URL = 'https://openrouter.ai/api/v1';

    /**
     * @return array<string, mixed>|null
     */
    public function generate(): ?array
    {
        $apiKey = config('services.openrouter.api_key');
        $model = config('blog.ai_model', self::DEFAULT_MODEL);
        $baseUrl = rtrim(config('services.openrouter.base_url', self::BASE_URL), '/');

        if (empty($apiKey)) {
            Log::warning('AiBlogGenerator: missing OpenRouter API key');

            return null;
        }

        try {
            $topicData = $this->generateTopic($apiKey, $baseUrl, $model);
            if ($topicData === null) {
                Log::warning('AiBlogGenerator: topic generation failed, using fallback');
                $topicData = $this->fallbackTopic();
            }

            $postData = $this->generatePost($apiKey, $baseUrl, $model, $topicData);
            if ($postData === null) {
                Log::error('AiBlogGenerator: post generation failed');

                return null;
            }

            $slug = Str::slug($postData['title'].'-'.uniqid());

            $blogPost = BlogPost::create([
                'slug' => $slug,
                'title' => $postData['title'],
                'subtitle' => $postData['subtitle'] ?? null,
                'excerpt' => $postData['excerpt'] ?? null,
                'content' => app(BlogHtmlSanitizer::class)->sanitize($postData['content']),
                'cover_image' => $postData['cover_image'] ?? null,
                'status' => 'published',
                'ai_metadata' => [
                    'model' => $model,
                    'topic_research' => $topicData['research_summary'] ?? null,
                    'topic_angle' => $topicData['angle'] ?? null,
                    'keywords' => $topicData['keywords'] ?? [],
                    'cost_cents' => $postData['cost_cents'] ?? null,
                    'generation_date' => now()->toIso8601String(),
                ],
                'related_sounds' => $postData['related_sounds'] ?? [],
                'related_creators' => $postData['related_creators'] ?? [],
                'published_at' => now(),
            ]);

            Log::info('AiBlogGenerator: blog post created', [
                'id' => $blogPost->id,
                'slug' => $blogPost->slug,
                'title' => $blogPost->title,
            ]);

            return [
                'blog_post' => $blogPost,
                'topic' => $topicData,
            ];
        } catch (\Throwable $e) {
            Log::error('AiBlogGenerator: exception during generation', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function generateTopic(string $apiKey, string $baseUrl, string $model): ?array
    {
        $recentTopics = BlogPost::latestPublished()
            ->limit(30)
            ->pluck('title')
            ->toArray();

        $recentTopicsList = empty($recentTopics)
            ? 'Aucun article précédent.'
            : 'Articles déjà publiés : '.implode(', ', array_slice($recentTopics, 0, 15)).'.';

        $prompt = <<<PROMPT
Tu es un journaliste culturel et naturaliste spécialisé en field recording, écologie sonore et paysages acoustiques.

{$recentTopicsList}

Trouve un sujet original pour un billet de blog d'aujourd'hui. Le sujet doit :
- Être en lien avec le field recording, la nature, l'écologie sonore, les paysages acoustiques, ou la biodiversité sonore
- Être différent des articles déjà publiés ci-dessus
- S'inspirer d'une actualité, d'une découverte récente, ou d'un phénomène naturel intéressant
- Être poétique, inspirant et respectueux de la nature

Utilise la recherche web pour trouver un angle d'actualité, une découverte récente, ou un phénomène naturel intéressant lié au field recording.

Réponds UNIQUEMENT en JSON strict sans markdown :
{
  "topic": "Titre du sujet",
  "angle": "Angle original et pourquoi il est pertinent aujourd'hui",
  "research_summary": "Résumé des découvertes web utilisées",
  "keywords": ["mot-clé1", "mot-clé2", "mot-clé3"]
}
PROMPT;

        $response = app(OpenRouterService::class)->postChatCompletion($apiKey, $baseUrl, [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un journaliste culturel et naturaliste. Tu conçois de vrais sujets de billets de blog poétiques et fouillés. Tu as accès à la recherche web. Réponds UNIQUEMENT en JSON strict.',
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
            Log::warning('AiBlogGenerator: topic API failed', [
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

        $content = $this->cleanJsonContent($content);
        $topic = json_decode($content, true);

        if (! is_array($topic) || empty($topic['topic'])) {
            Log::warning('AiBlogGenerator: invalid topic schema', ['content' => mb_substr($content, 0, 800)]);

            return null;
        }

        $topic['cost_cents'] = $this->extractCost($data);

        return $topic;
    }

    /**
     * @param  array<string, mixed>  $topicData
     * @return array<string, mixed>|null
     */
    private function generatePost(string $apiKey, string $baseUrl, string $model, array $topicData): ?array
    {
        $context = $this->buildSiteContext();

        $topic = $topicData['topic'] ?? 'L\'écoute comme art de vivre';
        $angle = $topicData['angle'] ?? '';
        $research = $topicData['research_summary'] ?? '';
        $keywords = is_array($topicData['keywords'] ?? null) ? $topicData['keywords'] : [];
        $keywordsList = implode(', ', $keywords);

        $prompt = <<<PROMPT
Tu es un écrivain naturaliste et poète, rédacteur du blog d'Arborisis — une plateforme de field recording dédiée aux sons de la nature.

━━ SUJET DU JOUR ━━
Titre : {$topic}
Angle : {$angle}
Recherches web : {$research}
Mots-clés : {$keywordsList}

━━ CONTEXTE ARBORISIS ━━
Date : {$context['date']}
Saison : {$context['season']}
Nombre total de sons : {$context['total_sounds']}

Top catégories :
{$this->formatList($context['top_categories'], 'name')}

Sons récents (7 derniers jours) :
{$this->formatSounds($context['recent_sounds'])}

Créateurs actifs récents :
{$this->formatCreators($context['recent_creators'])}

━━ CONSIGNES DE RÉDACTION ━━
- Rédige un billet de blog poétique et inspirant de 800 à 1500 mots.
- Le ton est doux, évocateur, respectueux de la nature. Pas de jargon technique excessif.
- Le billet doit raconter une histoire, une invitation à l'écoute, une réflexion sur le monde sonore.
- Intègre naturellement des liens vers les sons et créateurs d'Arborisis mentionnés ci-dessus. Choisis ceux qui s'accordent avec le sujet.
- Le contenu doit être au format HTML sémantique et aéré (h2, h3, p, em, strong, blockquote, ul/li, hr).
- Utilise des <hr> fins entre les grandes sections pour aérer la lecture.
- Varie la longueur des paragraphes : alterne paragraphes courts (1-2 phrases) et paragraphes longs (4-6 phrases).
- Utilise des blockquotes pour les réflexions poétiques ou les faits saillants.
- Le premier paragraphe doit être une accroche puissante, légèrement plus grande en style.
- Pas de balises markdown, pas de code blocks.
- Le HTML doit être propre et directement insérable dans une page web.

━━ LIENS DYNAMIQUES ━━
Pour chaque son ou créateur mentionné, utilise ce format exact dans le HTML :
<a href="/sounds/{slug}" data-arborisis-type="sound" data-arborisis-id="{id}">titre du son</a>
<a href="/creators/{slug}" data-arborisis-type="creator" data-arborisis-id="{id}">nom du créateur</a>
<a href="/map" data-arborisis-type="map">carte sonore</a>
<a href="/arborisis-map" data-arborisis-type="arborisis-map">points d'écoute</a>

━━ FORMAT JSON STRICT ━━
{
  "title": "Titre du billet (max 120 caractères)",
  "subtitle": "Sous-titre poétique (max 200 caractères)",
  "excerpt": "Résumé accrocheur de 2-3 phrases pour les previews",
  "content": "<article>...HTML complet du billet...</article>",
  "cover_image": "URL d'une image d'illustration (null si non trouvée)",
  "related_sounds": [
    {"sound_id": 123, "slug": "son-slug", "title": "Titre du son", "mention_context": "Pourquoi ce son est mentionné"}
  ],
  "related_creators": [
    {"user_id": 456, "slug": "createur-slug", "name": "Nom", "mention_context": "Pourquoi ce créateur est mentionné"}
  ]
}
PROMPT;

        $response = app(OpenRouterService::class)->postChatCompletion($apiKey, $baseUrl, [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un écrivain naturaliste et poète. Tu rédiges des billets de blog poétiques et inspirants sur le field recording et la nature. Tu utilises la recherche web pour enrichir ton propos. Tu réponds UNIQUEMENT en JSON strict, sans markdown, sans commentaire.',
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
            'temperature' => 0.75,
            'max_tokens' => 8192,
        ], timeoutSeconds: 180);

        if (! $response->successful()) {
            Log::warning('AiBlogGenerator: post API failed', [
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

        $content = $this->cleanJsonContent($content);
        $post = json_decode($content, true);

        if (! is_array($post) || empty($post['title']) || empty($post['content'])) {
            Log::warning('AiBlogGenerator: invalid post schema', ['content' => mb_substr($content, 0, 800)]);

            return null;
        }

        $post['cost_cents'] = $this->extractCost($data);

        return $post;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSiteContext(): array
    {
        $today = now();

        $totalSounds = Sound::count();

        $topCategories = \App\Models\Category::withCount('sounds')
            ->orderByDesc('sounds_count')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'name' => $c->name,
                'count' => $c->sounds_count,
            ])
            ->toArray();

        $recentSounds = Sound::with('category')
            ->whereDate('created_at', '>=', $today->copy()->subDays(7))
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Sound $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'slug' => $s->slug,
                'category' => $s->category?->name ?? 'Inconnu',
                'description' => $s->description,
            ])
            ->toArray();

        $recentCreators = User::withCount('sounds')
            ->whereDate('created_at', '>=', $today->copy()->subDays(30))
            ->orWhereHas('sounds', fn ($q) => $q->whereDate('created_at', '>=', $today->copy()->subDays(7)))
            ->orderByDesc('sounds_count')
            ->limit(5)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'slug' => $u->slug,
                'sounds_count' => $u->sounds_count,
            ])
            ->toArray();

        return [
            'date' => $today->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'season' => $this->seasonLabel($today),
            'total_sounds' => $totalSounds,
            'top_categories' => $topCategories,
            'recent_sounds' => $recentSounds,
            'recent_creators' => $recentCreators,
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

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function formatList(array $items, string $key): string
    {
        return implode("\n", array_map(fn ($item) => '- '.($item[$key] ?? 'Inconnu'), $items));
    }

    /**
     * @param  list<array<string, mixed>>  $sounds
     */
    private function formatSounds(array $sounds): string
    {
        $lines = [];
        foreach ($sounds as $sound) {
            $lines[] = '- "'.$sound['title'].'" ('.$sound['category'].') — slug: '.$sound['slug'].' — id: '.$sound['id'];
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<array<string, mixed>>  $creators
     */
    private function formatCreators(array $creators): string
    {
        $lines = [];
        foreach ($creators as $creator) {
            $lines[] = '- '.$creator['name'].' ('.$creator['sounds_count'].' sons) — slug: '.$creator['slug'].' — id: '.$creator['id'];
        }

        return implode("\n", $lines);
    }

    /**
     * @return array<string, mixed>
     */
    private function fallbackTopic(): array
    {
        return [
            'topic' => config('blog.fallback_topic', 'L\'écoute comme art de vivre'),
            'angle' => 'Une réflexion intemporelle sur notre rapport au monde sonore.',
            'research_summary' => 'Sujet générique sans recherche web.',
            'keywords' => ['field recording', 'écoute', 'nature'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractCost(array $data): ?int
    {
        $cost = $data['usage']['total_cost'] ?? null;

        return $cost !== null ? (int) round($cost * 100) : null;
    }

    private function cleanJsonContent(string $content): string
    {
        $content = trim($content);

        // Extract JSON from markdown code blocks
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $content, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/```\s*(\{.*?\})\s*```/s', $content, $matches)) {
            return trim($matches[1]);
        }

        // If no code block, try to find the first { and last }
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start !== false && $end !== false && $end > $start) {
            return substr($content, $start, $end - $start + 1);
        }

        return $content;
    }
}
