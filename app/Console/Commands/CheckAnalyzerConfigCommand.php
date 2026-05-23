<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class CheckAnalyzerConfigCommand extends Command
{
    protected $signature = 'analyzer:check';

    protected $description = 'Vérifier la configuration de l\'analyseur audio';

    public function handle(): int
    {
        $url = config('services.analyzer.url');
        $secret = config('services.analyzer.secret');
        
        $this->info('URL: ' . ($url ?: 'NON CONFIGURÉ'));
        $this->info('Secret: ' . ($secret ? substr($secret, 0, 10) . '...' : 'NON CONFIGURÉ'));
        
        if (!$url || !$secret) {
            $this->error('Configuration incomplète !');
            return 1;
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $secret,
            ])->timeout(5)->get($url . '/health');
            
            if ($response->successful()) {
                $this->info('✅ Connexion OK');
                return 0;
            }
            
            $this->error('❌ Erreur HTTP ' . $response->status());
            return 1;
        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            return 1;
        }
    }
}
