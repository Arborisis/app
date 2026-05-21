<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadioJingleResource\Pages;

use App\Filament\Resources\RadioJingleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRadioJingles extends ListRecords
{
    protected static string $resource = RadioJingleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
