<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Services\Mail\MailServerService;

class TestServiceConnections extends Command
{
    protected $signature = 'services:test';
    protected $description = 'Tester les connexions aux services externes (Railway + Cloudflare)';

    public function handle()
    {
        $this->info('🔍 Test des connexions aux services Arborisis...');
        $this->newLine();

        // 1. Test Mail Server
        $this->testMailServer();
        
        // 2. Test Radio Server
        $this->testRadioServer();
        
        // 3. Test Audio Analyzer
        $this->testAudioAnalyzer();
        
        // 4. Test Discord Bot
        $this->testDiscordBot();

        $this->newLine();
        $this->info('✅ Tests terminés !');
        return 0;
    }

    private function testMailServer()
    {
        $this->info('📧 Test Mail Server...');
        $url = config('services.mail_server.url');
        $token = config('services.mail_server.token');

        if (empty($url)) {
            $this->error('   ❌ MAIL_SERVER_URL non configuré');
            return;
        }

        try {
            $response = Http::timeout(5)->get($url . '/health');
            if ($response->successful()) {
                $this->info('   ✅ Mail Server accessible');
                $this->line('   URL: ' . $url);
            } else {
                $this->error('   ❌ Mail Server erreur HTTP ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Mail Server injoignable: ' . $e->getMessage());
        }
    }

    private function testRadioServer()
    {
        $this->info('📻 Test Radio Server...');
        $url = config('services.radio_server.url');

        if (empty($url)) {
            $this->error('   ❌ RADIO_SERVER_URL non configuré');
            return;
        }

        try {
            $response = Http::timeout(5)->get($url . '/health');
            if ($response->successful()) {
                $this->info('   ✅ Radio Server accessible');
                $this->line('   URL: ' . $url);
                $this->line('   Stream: ' . config('services.radio_server.public_stream_url'));
            } else {
                $this->error('   ❌ Radio Server erreur HTTP ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Radio Server injoignable: ' . $e->getMessage());
        }
    }

    private function testAudioAnalyzer()
    {
        $this->info('🎵 Test Audio Analyzer...');
        $url = config('services.analyzer.url');

        if (empty($url)) {
            $this->error('   ❌ ANALYZER_URL non configuré');
            return;
        }

        try {
            // Essayons un endpoint simple (ajustez selon votre API)
            $response = Http::timeout(5)->get($url . '/health');
            if ($response->successful()) {
                $this->info('   ✅ Audio Analyzer accessible');
                $this->line('   URL: ' . $url);
            } else {
                $this->error('   ❌ Audio Analyzer erreur HTTP ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Audio Analyzer injoignable: ' . $e->getMessage());
        }
    }

    private function testDiscordBot()
    {
        $this->info('🤖 Test Discord Bot...');
        $host = config('services.discord.bot_host');
        $port = config('services.discord.bot_port');

        if (empty($host)) {
            $this->error('   ❌ DISCORD_BOT_HOST non configuré');
            return;
        }

        try {
            $url = 'https://' . $host . ':' . $port;
            $response = Http::timeout(5)->get($url . '/health');
            if ($response->successful()) {
                $this->info('   ✅ Discord Bot accessible');
                $this->line('   URL: ' . $url);
            } else {
                $this->error('   ❌ Discord Bot erreur HTTP ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('   ❌ Discord Bot injoignable: ' . $e->getMessage());
        }
    }
}
