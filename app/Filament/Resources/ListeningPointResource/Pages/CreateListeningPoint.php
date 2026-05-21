<?php

declare(strict_types=1);

namespace App\Filament\Resources\ListeningPointResource\Pages;

use App\Filament\Resources\ListeningPointResource;
use Filament\Resources\Pages\CreateRecord;

class CreateListeningPoint extends CreateRecord
{
    protected static string $resource = ListeningPointResource::class;
}
