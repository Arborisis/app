<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListeningPointResource\Pages;

use App\Filament\Resources\ListeningPointResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListListeningPoints extends ListRecords
{
    protected static string $resource = ListeningPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
