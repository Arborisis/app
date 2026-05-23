<?php

namespace App\Providers;

use App\Mail\Transports\MailServerTransport;
use App\Services\Mail\MailServerService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Mail\MailManager;

class MailServerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MailServerService::class, function () {
            return new MailServerService();
        });
    }

    public function boot(): void
    {
        $this->app->extend('mail.manager', function (MailManager $manager) {
            $manager->extend('mailserver', function () {
                return new MailServerTransport(
                    $this->app->make(MailServerService::class)
                );
            });

            return $manager;
        });
    }
}
