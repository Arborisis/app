<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Models\RadioPodcast;
use App\Services\AI\OpenRouterService;
use Illuminate\Support\Facades\Log;

class PodcastIdeaGeneratorService
{
    private const HISTORY_WEEKS = 12;

    public function __construct(
        private readonly OpenRouterService $openRouter,
    ) {}

    /**
     * Génère un thème unique pour le podcast de la semaine.
     * Vérifie l'historique pour éviter les répétitions.
     */
    public function generateWeeklyTopic(): ?array
    {
        $recentThemes = $this->recentThemes();

        $result = $this->openRouter->generatePodcastTopic($recentThemes);

        if (! $result) {
            Log::warning('Podcast idea generator: failed to generate topic');

            return null;
        }

        $topic = $result['topic'];

        // Vérification supplémentaire de non-répétition (insensible à la casse)
        $normalizedNew = mb_strtolower(trim($topic['theme']));
        foreach ($recentThemes as $recent) {
            similar_text($normalizedNew, mb_strtolower(trim($recent)), $percent);
            if ($percent > 75) {
                Log::warning('Podcast idea generator: topic too similar to recent one', [
                    'new' => $topic['theme'],
                    'similar_to' => $recent,
                    'similarity' => $percent,
                ]);

                // On pourrait réessayer ici, mais pour éviter les boucles infinies on retourne null
                return null;
            }
        }

        Log::info('Podcast idea generator: weekly topic selected', [
            'theme' => $topic['theme'],
            'keywords' => $topic['keywords'] ?? [],
        ]);

        return [
            'theme' => $topic['theme'],
            'angle' => $topic['angle'] ?? '',
            'research_summary' => $topic['research_summary'] ?? '',
            'keywords' => $topic['keywords'] ?? [],
            'cost_cents' => $result['cost_cents'],
        ];
    }

    /**
     * @return list<string>
     */
    public function recentThemes(): array
    {
        return RadioPodcast::query()
            ->whereNotNull('theme')
            ->where('created_at', '>=', now()->subWeeks(self::HISTORY_WEEKS))
            ->orderByDesc('created_at')
            ->limit(50)
            ->pluck('theme')
            ->filter()
            ->all();
    }
}
