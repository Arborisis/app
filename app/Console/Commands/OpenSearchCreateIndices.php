<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\OpenSearch\OpenSearchIndexService;
use Illuminate\Console\Command;

class OpenSearchCreateIndices extends Command
{
    protected $signature = 'search:create-indices';
    protected $description = 'Create OpenSearch indices with mappings';

    public function handle(OpenSearchIndexService $service): int
    {
        if (! $service->isAvailable()) {
            $this->error('OpenSearch is not available.');
            return self::FAILURE;
        }

        $service->createIndices();
        $this->info('OpenSearch indices created successfully.');

        return self::SUCCESS;
    }
}
