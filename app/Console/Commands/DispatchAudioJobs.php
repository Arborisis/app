<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AudioWorkerDispatchService;
use Illuminate\Console\Command;

class DispatchAudioJobs extends Command
{
    protected $signature = 'audio:dispatch-jobs 
                            {--reassign-timeouts : Réassigner les jobs en timeout}
                            {--loop : Boucle infinie pour le mode daemon}';

    protected $description = 'Dispatch les jobs audio vers les workers disponibles';

    public function __construct(
        private AudioWorkerDispatchService $dispatchService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Démarrage du dispatch audio...');

        if ($this->option('loop')) {
            $this->info('Mode daemon activé (Ctrl+C pour arrêter)');
            
            while (true) {
                $this->processBatch();
                sleep(10);
            }
        }

        $this->processBatch();

        return self::SUCCESS;
    }

    private function processBatch(): void
    {
        if ($this->option('reassign-timeouts')) {
            $this->info('Réassignation des jobs en timeout...');
            $this->dispatchService->reassignTimeoutJobs();
        }

        $this->info('Dispatch des jobs en attente...');
        $this->dispatchService->dispatchPendingJobs();

        $stats = $this->dispatchService->getWorkerStats();
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Workers totaux', $stats['total_workers']],
                ['Workers en ligne', $stats['online_workers']],
                ['Workers occupés', $stats['busy_workers']],
                ['Jobs en attente', $stats['pending_jobs']],
                ['Jobs en cours', $stats['processing_jobs']],
                ['Jobs complétés (aujourd\'hui)', $stats['completed_today']],
            ]
        );
    }
}