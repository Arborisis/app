<?php

namespace App\Filament\Resources\EchoTransactionResource\Pages;

use App\Filament\Resources\EchoTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEchoTransaction extends EditRecord
{
    protected static string $resource = EchoTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
