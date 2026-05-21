<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadioScheduleResource\Pages;

use App\Filament\Resources\RadioScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRadioSchedules extends ListRecords
{
    protected static string $resource = RadioScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
