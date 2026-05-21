<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadioScheduleResource\Pages;

use App\Filament\Resources\RadioScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRadioSchedule extends EditRecord
{
    protected static string $resource = RadioScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
