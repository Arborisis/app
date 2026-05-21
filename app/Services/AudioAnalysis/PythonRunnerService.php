<?php

declare(strict_types=1);

namespace App\Services\AudioAnalysis;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PythonRunnerService
{
    private string $pythonPath;
    private string $basePath;
    private int $timeout;

    public function __construct()
    {
        $this->pythonPath = config('services.python.path', 'python3');
        $this->basePath = base_path('python');
        $this->timeout = config('services.python.timeout', 300);
    }

    /**
     * Exécute un script Python avec les arguments donnés.
     *
     * @return array{stdout: string, stderr: string, exitCode: int}
     *
     * @throws \RuntimeException
     */
    public function run(string $script, array $args): array
    {
        $this->validateEnvironment();

        $scriptPath = "{$this->basePath}/{$script}";
        if (! file_exists($scriptPath)) {
            throw new \RuntimeException("Script Python introuvable: {$scriptPath}");
        }

        $command = array_merge([$this->pythonPath, $scriptPath], $args);
        $process = new Process($command, $this->basePath);
        $process->setTimeout($this->timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return [
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
            'exitCode' => $process->getExitCode(),
        ];
    }

    /**
     * Exécute le pipeline CLI et retourne le JSON parsé.
     *
     * @return array
     *
     * @throws \RuntimeException
     */
    public function runAnalysisPipeline(string $audioPath, string $outputDir, array $config = []): array
    {
        $configJson = json_encode($config);
        $result = $this->run('cli.py', [
            '--input', $audioPath,
            '--output', $outputDir,
            '--config', $configJson,
        ]);

        $output = trim($result['stdout']);
        if (empty($output)) {
            throw new \RuntimeException('Le pipeline Python a retourné une sortie vide');
        }

        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Erreur parsing JSON Python: '.json_last_error_msg());
        }

        if (empty($data['success'])) {
            $error = $data['error'] ?? 'Erreur inconnue du pipeline Python';
            throw new \RuntimeException($error);
        }

        return $data;
    }

    public function validateEnvironment(): void
    {
        $process = new Process([$this->pythonPath, '--version']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Python3 n\'est pas disponible sur ce système.');
        }
    }

    public function isAvailable(): bool
    {
        try {
            $this->validateEnvironment();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
