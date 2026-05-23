<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\MailServerServiceProvider;
use App\Providers\OpenSearchServiceProvider;
use Illuminate\Broadcasting\BroadcastServiceProvider;

return [
    AppServiceProvider::class,
    MailServerServiceProvider::class,
    OpenSearchServiceProvider::class,
    AdminPanelProvider::class,
    BroadcastServiceProvider::class,
];
