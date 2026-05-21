<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SoundFile;
use App\Services\Radio\RadioConversionService;

class SoundFileObserver
{
    public function __construct(
        private readonly RadioConversionService $conversionService
    ) {}

    public function created(SoundFile $soundFile): void
    {
        if ($this->conversionService->isConvertible($soundFile)) {
            dispatch(function () use ($soundFile) {
                $this->conversionService->convert($soundFile->fresh());
            })->afterResponse();
        }
    }

    public function deleted(SoundFile $soundFile): void
    {
        if ($soundFile->radio_path) {
            \Illuminate\Support\Facades\Storage::disk($soundFile->disk)->delete($soundFile->radio_path);
        }
    }
}
