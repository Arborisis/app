<?php

declare(strict_types=1);

namespace App\Filament\Resources\BirdnetDetectionResource\Pages;

use App\Filament\Resources\BirdnetDetectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBirdnetDetection extends EditRecord
{
    protected static string $resource = BirdnetDetectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
