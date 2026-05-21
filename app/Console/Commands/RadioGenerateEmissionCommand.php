<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Radio\RadioEmissionGenerationService;
use Illuminate\Console\Command;

class RadioGenerateEmissionCommand extends Command
{
    protected $signature = 'radio:generate-emission
                            {--force : Bypass canGenerate checks}';

    protected $description = 'Generate a full AI-hosted radio emission';

    public function handle(RadioEmissionGenerationService $service): int
    {
        if (! $this->option('force') && ! $service->canGenerate()) {
            $this->error('Emission generation is not possible. Check API keys or pending queue.');

            return self::FAILURE;
        }

        $this->info('Starting emission generation...');

        $podcast = $service->generate();

        if (! $podcast) {
            $this->error('Emission generation failed. Check logs for details.');

            return self::FAILURE;
        }

        $this->info('Emission generated successfully!');
        $this->table(
            ['Attribute', 'Value'],
            [
                ['ID', $podcast->id],
                ['Title', $podcast->title],
                ['Status', $podcast->status->value],
                ['Duration', $podcast->actual_duration_seconds . 's'],
                ['Path', $podcast->path],
            ]
        );

        return self::SUCCESS;
    }
}
