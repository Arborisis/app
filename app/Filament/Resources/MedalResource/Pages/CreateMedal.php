<?php

declare(strict_types=1);

namespace App\Filament\Resources\MedalResource\Pages;

use App\Filament\Resources\MedalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMedal extends CreateRecord
{
    protected static string $resource = MedalResource::class;
}
