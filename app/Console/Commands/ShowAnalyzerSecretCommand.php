<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ShowAnalyzerSecretCommand extends Command
{
    protected $signature = 'analyzer:show-secret';

    protected $description = 'Afficher le token analyzer actuel';

    public function handle(): int
    {
        $secret = config('services.analyzer.secret');
        $envSecret = env('ANALYZER_SECRET');
        
        $this->info('=== Token Analyzer ===');
        $this->info('config(): ' . ($secret ?: 'VIDE'));
        $this->info('env(): ' . ($envSecret ?: 'VIDE'));
        $this->info('Longueur config: ' . strlen($secret));
        $this->info('Longueur env: ' . strlen($envSecret));
        $this->info('Match: ' . ($secret === $envSecret ? 'OUI' : 'NON'));
        
        return 0;
    }
}
