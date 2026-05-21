<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Radio\RadioFlashGenerationService;
use Illuminate\Console\Command;

class RadioGenerateFlashCommand extends Command
{
    protected $signature = 'radio:generate-flash
                            {--force : Bypass canGenerate checks}';

    protected $description = 'Generate an AI flash info for the radio host';

    public function handle(RadioFlashGenerationService $service): int
    {
        if (! $this->option('force') && ! $service->canGenerate()) {
            $this->error('Flash generation is not possible. Check API keys or pending queue.');

            return self::FAILURE;
        }

        $this->info('Starting flash generation...');

        $podcast = $service->generate();

        if (! $podcast) {
            $this->error('Flash generation failed. Check logs for details.');

            return self::FAILURE;
        }

        $this->info('Flash generated successfully!');
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
