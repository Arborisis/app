<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\OpenSearchServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    BroadcastServiceProvider::class,
];
