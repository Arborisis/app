<?php

namespace App\Console\Commands;

use App\Enums\AnalysisStatus;
use App\Jobs\RequestAudioAnalysis;
use App\Models\Sound;
use Illuminate\Console\Command;

class RetryAnalysisCommand extends Command
{
    protected $signature = 'sound:retry-analysis 
                            {sound_id : ID du son}
                            {--force : Forcer la réanalyse même si déjà analysé}';

    protected $description = 'Relancer l\'analyse audio d\'un son';

    public function handle(): int
    {
        $soundId = $this->argument('sound_id');
        
        $sound = Sound::with('soundFile')->find($soundId);
        
        if (!$sound) {
            $this->error("Son #{$soundId} non trouvé.");
            return 1;
        }
        
        if (!$sound->soundFile) {
            $this->error("Fichier audio manquant pour le son #{$soundId}.");
            return 1;
        }
        
        $this->info("Son : {$sound->title}");
        $this->info("Fichier : {$sound->soundFile->path}");
        
        $force = $this->option('force');
        
        RequestAudioAnalysis::dispatch($sound->id, $sound->soundFile->path, $force);
        
        $this->info("✅ Analyse relancée pour le son #{$soundId}" . ($force ? ' (forcée)' : ''));
        
        return 0;
    }
}
