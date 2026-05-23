<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugAnalyzerTokenCommand extends Command
{
    protected $signature = 'analyzer:debug-token';

    protected $description = 'Debug analyzer token';

    public function handle(): int
    {
        $secret = config('services.analyzer.secret');
        
        $this->info('Longueur: ' . strlen($secret));
        $this->info('Token: ' . $secret);
        $this->info('');
        
        // Test avec le token actuel
        $url = config('services.analyzer.url');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secret,
        ])->timeout(5)->post($url . '/analyze', [
            'sound_id' => 99999,
            'original_r2_key' => 'sounds/test.mp3',
            'force' => true,
        ]);
        
        $this->info('Status: ' . $response->status());
        
        return 0;
    }
}
