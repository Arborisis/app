<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RequestAudioAnalysis;
use App\Models\Sound;
use App\Models\SoundAnalysis;
use Illuminate\Console\Command;

class ReanalyzeSounds extends Command
{
    protected $signature = 'sounds:reanalyze
                            {--force : Supprimer les analyses existantes avant de relancer}
                            {--only-missing : Ne traiter que les sons sans analyse complétée}
                            {--chunk=50 : Nombre de sons traités par batch}';

    protected $description = 'Relance l\'analyse audio sur tous les sons (ou seulement ceux sans analyse)';

    public function handle(): int
    {
        $force = $this->option('force');
        $onlyMissing = $this->option('only-missing');
        $chunkSize = (int) $this->option('chunk');

        $query = Sound::query()
            ->whereHas('soundFile');

        if ($onlyMissing) {
            $query->whereDoesntHave('soundAnalysis', fn ($q) => $q->whereNotNull('processed_at'));
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('Aucun son à analyser.');
            return self::SUCCESS;
        }

        $this->info("{$total} son(s) à analyser.");

        if ($force && ! $onlyMissing) {
            if (! $this->confirm('Cela va supprimer TOUTES les analyses existantes. Continuer ?')) {
                return self::SUCCESS;
            }
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunkById($chunkSize, function ($sounds) use ($force, $bar) {
            foreach ($sounds as $sound) {
                if ($force) {
                    $sound->soundAnalysis?->delete();
                }

                // Force re-create as pending to ensure re-analysis
                SoundAnalysis::updateOrCreate(
                    ['sound_id' => $sound->id],
                    ['status' => \App\Enums\AnalysisStatus::PENDING, 'failed_reason' => null]
                );

                $r2Key = $sound->soundFile?->path ?? "sounds/original/{$sound->id}/audio";
                RequestAudioAnalysis::dispatch($sound->id, $r2Key, force: true);
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info('Jobs dispatchés ! Assurez-vous que le worker de queue est actif :');
        $this->line('  php artisan queue:work');

        return self::SUCCESS;
    }
}
