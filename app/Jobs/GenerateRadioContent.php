<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\RadioShowType;
use App\Services\Radio\RadioEmissionGenerationService;
use App\Services\Radio\RadioFlashGenerationService;
use App\Services\Radio\RadioPodcastGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateRadioContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(public readonly RadioShowType $showType)
    {
        $this->onQueue('radio');
    }

    public function handle(
        RadioPodcastGenerationService $podcast,
        RadioFlashGenerationService $flash,
        RadioEmissionGenerationService $emission,
    ): void {
        match ($this->showType) {
            RadioShowType::Podcast  => $podcast->generate(),
            RadioShowType::Flash    => $flash->generate(),
            RadioShowType::Emission => $emission->generate(),
        };
    }
}
