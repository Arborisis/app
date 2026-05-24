<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestAnalyzerRequestCommand extends Command
{
    protected $signature = 'analyzer:test-request';

    protected $description = 'Tester une requête directe au worker';

    public function handle(): int
    {
        $url = config('services.analyzer.url');
        $secret = config('services.analyzer.secret');
        
        $this->info('Token utilisé: ' . substr($secret, 0, 10) . '...');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secret,
        ])->timeout(5)->post($url . '/analyze', [
            'sound_id' => 99999,
            'original_r2_key' => 'sounds/test.mp3',
            'force' => true,
        ]);
        
        $this->info('Status: ' . $response->status());
        $this->info('Body: ' . $response->body());
        
        return 0;
    }
}
