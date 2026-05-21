<?php

declare(strict_types=1);

namespace App\Filament\Resources\BirdnetDetectionResource\Pages;

use App\Filament\Resources\BirdnetDetectionResource;
use Filament\Resources\Pages\ListRecords;

class ListBirdnetDetections extends ListRecords
{
    protected static string $resource = BirdnetDetectionResource::class;
}
