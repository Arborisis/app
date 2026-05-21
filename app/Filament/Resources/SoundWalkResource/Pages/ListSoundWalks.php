<?php

declare(strict_types=1);

namespace App\Filament\Resources\SoundWalkResource\Pages;

use App\Filament\Resources\SoundWalkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoundWalks extends ListRecords
{
    protected static string $resource = SoundWalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
