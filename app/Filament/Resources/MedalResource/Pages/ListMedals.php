<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedalResource\Pages;

use App\Filament\Resources\MedalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedals extends ListRecords
{
    protected static string $resource = MedalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
