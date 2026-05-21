<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Radio\RadioPodcastGenerationService;
use Illuminate\Console\Command;

class RadioGeneratePodcastCommand extends Command
{
    protected $signature = 'radio:generate-podcast
                            {--force : Bypass canGenerate checks}';

    protected $description = 'Generate a long-form AI podcast episode for the radio';

    public function handle(RadioPodcastGenerationService $service): int
    {
        if (! $this->option('force') && ! $service->canGenerate()) {
            $this->error('Podcast generation is not possible. Check API keys or pending queue.');

            return self::FAILURE;
        }

        $this->info('Starting podcast generation...');

        $podcast = $service->generate();

        if (! $podcast) {
            $this->error('Podcast generation failed. Check logs for details.');

            return self::FAILURE;
        }

        $this->info('Podcast generated successfully!');
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
