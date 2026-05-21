<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailySoundIdea;
use App\Services\SoundIdeas\AiSoundIdeaGeneratorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GenerateDailySoundIdeas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $existingToday = DailySoundIdea::today()->exists();

        if ($existingToday) {
            Log::info('GenerateDailySoundIdeas: ideas already exist for today');

            return;
        }

        $result = app(AiSoundIdeaGeneratorService::class)->generateDailyIdeas();
        $ideas = $result['ideas'] ?? [];
        $theme = $result['theme'] ?? null;

        if (! empty($ideas)) {
            foreach ($ideas as $ideaData) {
                DailySoundIdea::create([
                    'date' => today(),
                    'theme' => $theme,
                    ...$ideaData,
                ]);
            }

            if (! empty($theme)) {
                Cache::put('daily_sound_idea_theme', $theme, now()->endOfDay());
            }

            Log::info('GenerateDailySoundIdeas: AI ideas created', ['count' => count($ideas)]);

            return;
        }

        $this->createFallbackIdeas($theme);
    }

    private function createFallbackIdeas(?string $theme): void
    {
        $fallbackIdeas = [
            [
                'title' => 'Le murmure de l\'aube',
                'description' => 'Avant que le soleil ne chauffe l\'air, le monde tient sa respiration. Trouvez un point d\'eau calme et enregistrez la transition entre le silence nocturne et le premier éveil des oiseaux.',
                'difficulty' => 'easy',
                'tags' => ['aube', 'oiseaux', 'eau'],
                'time_of_day' => 'matin',
            ],
            [
                'title' => 'Sous la canopée humide',
                'description' => 'Après une pluie, la forêt entière change de timbre. Les gouttes tombent des feuilles avec une rythmique aléatoire qui forme un tapis sonore unique. Approchez votre micro du tronc d\'un arbre majestueux.',
                'difficulty' => 'easy',
                'tags' => ['forêt', 'pluie', 'feuillage'],
                'time_of_day' => 'toute la journée',
            ],
            [
                'title' => 'Le vent dans les herbes hautes',
                'description' => 'Dans une prairie ou un champ en friche, les graminées deviennent un instrument à vent naturel. Positionnez votre micro au niveau des épis et laissez la brise composer.',
                'difficulty' => 'easy',
                'tags' => ['vent', 'prairie', 'herbes'],
                'time_of_day' => 'après-midi',
            ],
            [
                'title' => 'Reflets nocturnes',
                'description' => 'Au bord d\'un lac ou d\'une rivière tranquille, la nuit transforme chaque bruit en écho. Enregistrez le dialogue entre l\'eau dormante et les créatures nocturnes qui s\'en approchent.',
                'difficulty' => 'medium',
                'tags' => ['nuit', 'eau', 'crépuscule'],
                'time_of_day' => 'nuit',
            ],
            [
                'title' => 'La texture du silence minéral',
                'description' => 'Dans un environnement rocheux — falaise, carrière abandonnée ou rivière de pierres — le silence n\'est jamais absolu. Captez les micro-événements : grésillement de roche, glissement de sable, réverbération du vent.',
                'difficulty' => 'medium',
                'tags' => ['minéral', 'roche', 'micro-sons'],
                'time_of_day' => 'toute la journée',
            ],
        ];

        foreach ($fallbackIdeas as $ideaData) {
            DailySoundIdea::create([
                'date' => today(),
                'theme' => $theme ?? 'Explorer le monde en mouvement',
                ...$ideaData,
                'season_context' => null,
                'weather_context' => null,
            ]);
        }

        Log::info('GenerateDailySoundIdeas: fallback ideas created', ['count' => count($fallbackIdeas)]);
    }
}
