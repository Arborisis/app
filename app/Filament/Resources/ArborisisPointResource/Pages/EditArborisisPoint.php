<?php

declare(strict_types=1);

namespace App\Filament\Resources\ArborisisPointResource\Pages;

use App\Filament\Resources\ArborisisPointResource;
use App\Models\ArborisisPoint;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArborisisPoint extends EditRecord
{
    protected static string $resource = ArborisisPointResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $data['latitude'] = $record->latitude;
        $data['longitude'] = $record->longitude;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $coordinates = ArborisisPoint::publicCoordinates(
            (float) $data['latitude'],
            (float) $data['longitude'],
            $data['nature_sensitivity_level'] ?? null,
        );

        $data['approximate_latitude'] = $coordinates['approximate_latitude'];
        $data['approximate_longitude'] = $coordinates['approximate_longitude'];

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
