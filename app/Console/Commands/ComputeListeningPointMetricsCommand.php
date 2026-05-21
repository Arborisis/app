<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ListeningPoint;
use App\Services\Scientific\ListeningPointMetricsService;
use Illuminate\Console\Command;

class ComputeListeningPointMetricsCommand extends Command
{
    protected $signature = 'listening-points:compute-metrics
                            {--point-id= : ID d\'un point spécifique}
                            {--dry-run : Simuler sans écrire en base}';

    protected $description = 'Calcule les métriques scientifiques agrégées pour les points d\'écoute';

    public function handle(ListeningPointMetricsService $service): int
    {
        $dryRun = $this->option('dry-run');
        $pointId = $this->option('point-id');

        $query = ListeningPoint::approved()
            ->whereHas('sounds', fn ($q) => $q->public());

        if ($pointId) {
            $query->where('id', (int) $pointId);
        }

        $total = $query->count();

        if ($total === 0) {
            $this->warn('Aucun point d\'écoute trouvé.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Traitement de %d point(s) d\'écoute...', $total));

        $progress = $this->output->createProgressBar($total);
        $progress->start();

        $query->chunk(50, function ($points) use ($service, $dryRun, $progress) {
            foreach ($points as $point) {
                if (! $dryRun) {
                    $service->computeAllMetrics($point);
                }
                $progress->advance();
            }
        });

        $progress->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info('Simulation terminée. Aucune donnée n\'a été écrite.');
        } else {
            $this->info('Métriques calculées et stockées avec succès.');
        }

        return self::SUCCESS;
    }
}
