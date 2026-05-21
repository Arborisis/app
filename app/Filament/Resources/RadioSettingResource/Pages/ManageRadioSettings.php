<?php

declare(strict_types=1);

namespace App\Filament\Resources\RadioSettingResource\Pages;

use App\Filament\Resources\RadioSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRadioSettings extends ManageRecords
{
    protected static string $resource = RadioSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn (): bool => $this->getModel()::query()->doesntExist()),
        ];
    }
}
