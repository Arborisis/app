<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Sound;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate the public sitemap.xml';

    public function handle(): int
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $now = now()->toIso8601String();

        $urls = [];

        // Static pages
        $staticRoutes = [
            'landing',
            'sounds.index',
            'map.index',
            'creators.index',
            'transparency',
            'echo.info',
            'mission',
            'charte',
            'radio.index',
            'radio.shows.index',
        ];

        foreach ($staticRoutes as $routeName) {
            if (Route::has($routeName)) {
                $urls[] = [
                    'loc' => $baseUrl . route($routeName, [], false),
                    'lastmod' => $now,
                    'changefreq' => 'daily',
                    'priority' => $routeName === 'landing' ? '1.0' : '0.8',
                ];
            }
        }

        // Public sounds
        Sound::public()
            ->select('slug', 'updated_at')
            ->chunk(200, function ($sounds) use (&$urls, $baseUrl) {
                foreach ($sounds as $sound) {
                    $urls[] = [
                        'loc' => $baseUrl . route('sounds.show', $sound->slug, false),
                        'lastmod' => $sound->updated_at->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                    $urls[] = [
                        'loc' => $baseUrl . route('sounds.analysis.show', $sound->slug, false),
                        'lastmod' => $sound->updated_at->toIso8601String(),
                        'changefreq' => 'monthly',
                        'priority' => '0.5',
                    ];
                }
            });

        // Creators with public sounds
        User::whereHas('sounds', fn ($q) => $q->public())
            ->select('slug', 'updated_at')
            ->chunk(200, function ($users) use (&$urls, $baseUrl) {
                foreach ($users as $user) {
                    $urls[] = [
                        'loc' => $baseUrl . route('creators.show', $user->slug, false),
                        'lastmod' => $user->updated_at->toIso8601String(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . e($url['loc']) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $url['lastmod'] . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . $url['changefreq'] . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $url['priority'] . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>' . "\n";

        $path = public_path('sitemap.xml');
        file_put_contents($path, $xml);

        $this->info('Sitemap generated: ' . $path);
        $this->info('URLs: ' . count($urls));

        return self::SUCCESS;
    }
}
