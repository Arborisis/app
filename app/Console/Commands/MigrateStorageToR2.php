<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SoundFile;
use App\Models\SoundVisualization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MigrateStorageToR2 extends Command
{
    protected $signature = 'storage:migrate-to-r2
                            {--source=audio : Disque source (audio ou s3)}
                            {--target=r2 : Disque cible}
                            {--chunk=50 : Nombre de fichiers par chunk}
                            {--dry-run : Simuler sans copier}';

    protected $description = 'Migre les fichiers du stockage Contabo S3 vers Cloudflare R2';

    public function handle(): int
    {
        $sourceDisk = $this->option('source');
        $targetDisk = $this->option('target');
        $chunkSize = (int) $this->option('chunk');
        $dryRun = $this->option('dry-run');

        if (! $dryRun && ! $this->confirm(
            "Cette commande va copier tous les fichiers du disque '{$sourceDisk}' vers '{$targetDisk}' et mettre à jour la base de données. Continuer ?"
        )) {
            $this->info('Migration annulée.');
            return self::SUCCESS;
        }

        $this->info("Migration {$sourceDisk} → {$targetDisk}" . ($dryRun ? ' (DRY RUN)' : ''));

        $totalMigrated = 0;
        $totalFailed = 0;

        // 1. Sound files
        $this->newLine();
        $this->info('=== Migration des fichiers audio (sound_files) ===');

        SoundFile::whereIn('disk', [$sourceDisk, 's3', 'audio'])
            ->chunkById($chunkSize, function ($files) use ($sourceDisk, $targetDisk, $dryRun, &$totalMigrated, &$totalFailed) {
                foreach ($files as $file) {
                    $result = $this->migrateFile($sourceDisk, $targetDisk, $file->path, $dryRun);

                    if ($result === true) {
                        if (! $dryRun) {
                            $file->update(['disk' => $targetDisk]);
                        }
                        $totalMigrated++;
                        $this->info("[OK] {$file->path}");
                    } elseif ($result === false) {
                        $totalFailed++;
                        $this->error("[KO] {$file->path}");
                    } else {
                        // File does not exist on source, skip
                        $this->warn("[SKIP] {$file->path} (introuvable sur {$sourceDisk})");
                    }
                }
            });

        // 2. Sound visualizations
        $this->newLine();
        $this->info('=== Migration des visualisations (sound_visualizations) ===');

        SoundVisualization::whereIn('disk', [$sourceDisk, 's3', 'audio'])
            ->chunkById($chunkSize, function ($files) use ($sourceDisk, $targetDisk, $dryRun, &$totalMigrated, &$totalFailed) {
                foreach ($files as $file) {
                    $result = $this->migrateFile($sourceDisk, $targetDisk, $file->file_path, $dryRun);

                    if ($result === true) {
                        if (! $dryRun) {
                            $file->update(['disk' => $targetDisk]);
                        }
                        $totalMigrated++;
                        $this->info("[OK] {$file->file_path}");
                    } elseif ($result === false) {
                        $totalFailed++;
                        $this->error("[KO] {$file->file_path}");
                    } else {
                        $this->warn("[SKIP] {$file->file_path} (introuvable sur {$sourceDisk})");
                    }
                }
            });

        // 3. Covers (stored in sounds.cover_image – disk inferred from soundFile)
        $this->newLine();
        $this->info('=== Migration des covers (sounds.cover_image) ===');

        DB::table('sounds')
            ->whereNotNull('cover_image')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('sound_files')
                    ->whereColumn('sound_files.sound_id', 'sounds.id')
                    ->whereIn('sound_files.disk', ['audio', 's3']);
            })
            ->chunkById($chunkSize, function ($sounds) use ($sourceDisk, $targetDisk, $dryRun, &$totalMigrated, &$totalFailed) {
                foreach ($sounds as $sound) {
                    $result = $this->migrateFile($sourceDisk, $targetDisk, $sound->cover_image, $dryRun);

                    if ($result === true) {
                        // Cover disk is inferred from soundFile, no direct DB update needed
                        $totalMigrated++;
                        $this->info("[OK] cover {$sound->cover_image}");
                    } elseif ($result === false) {
                        $totalFailed++;
                        $this->error("[KO] cover {$sound->cover_image}");
                    } else {
                        $this->warn("[SKIP] cover {$sound->cover_image} (introuvable sur {$sourceDisk})");
                    }
                }
            });

        $this->newLine();
        $this->info("=== Résumé ===");
        $this->info("Migrés  : {$totalMigrated}");
        $this->error("Échecs  : {$totalFailed}");

        if ($totalFailed > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return bool|null true = success, false = failure, null = source file not found
     */
    private function migrateFile(string $sourceDisk, string $targetDisk, string $path): ?bool
    {
        $source = Storage::disk($sourceDisk);
        $target = Storage::disk($targetDisk);

        if (! $source->exists($path)) {
            return null;
        }

        if ($target->exists($path)) {
            // Already migrated
            return true;
        }

        try {
            $stream = $source->readStream($path);
            $target->writeStream($path, $stream);

            if (is_resource($stream)) {
                fclose($stream);
            }

            return true;
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }
}
