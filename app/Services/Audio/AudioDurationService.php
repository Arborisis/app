<?php

declare(strict_types=1);

namespace App\Services\Audio;

use Symfony\Component\Process\Process;

class AudioDurationService
{
    /**
     * Extrait la durée en secondes d'un fichier audio local via ffprobe.
     * Retourne null si ffprobe échoue ou si la durée n'est pas disponible.
     */
    public function getDuration(string $localPath): ?float
    {
        if (! file_exists($localPath)) {
            return null;
        }

        $process = new Process([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $localPath,
        ]);

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());
        $duration = is_numeric($output) ? (float) $output : null;

        return $duration > 0 ? $duration : null;
    }
}
