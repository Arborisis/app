<?php

declare(strict_types=1);

namespace App\Filament\Resources\SoundWalkResource\Pages;

use App\Filament\Resources\SoundWalkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoundWalk extends EditRecord
{
    protected static string $resource = SoundWalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }
}
