<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateAnalyzerToken extends Command
{
    protected $signature = 'analyzer:generate-token
                            {--show : Affiche le token sans le sauvegarder}';

    protected $description = 'Génère le ANALYZER_INTERNAL_API_TOKEN pour la communication orchestrateur → Laravel';

    public function handle(): int
    {
        $token = bin2hex(random_bytes(32));

        if ($this->option('show')) {
            $this->info('Token généré (à copier dans vos variables d\'environnement Laravel Cloud) :');
            $this->line('');
            $this->line("<fg=yellow>ANALYZER_INTERNAL_API_TOKEN={$token}</>");
            $this->line('');
            return self::SUCCESS;
        }

        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        if (str_contains($envContent, 'ANALYZER_INTERNAL_API_TOKEN=')) {
            $envContent = preg_replace(
                '/ANALYZER_INTERNAL_API_TOKEN=.*/',
                "ANALYZER_INTERNAL_API_TOKEN={$token}",
                $envContent
            );
        } else {
            $envContent .= "\nANALYZER_INTERNAL_API_TOKEN={$token}\n";
        }

        file_put_contents($envPath, $envContent);

        $this->info('Token généré et sauvegardé dans .env');
        $this->line('');
        $this->line("<fg=yellow>ANALYZER_INTERNAL_API_TOKEN={$token}</>");
        $this->line('');
        $this->info('→ Copie cette variable dans Laravel Cloud pour que l\'orchestrateur puisse communiquer avec ton app.');

        return self::SUCCESS;
    }
}
