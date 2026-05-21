<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Discord\DiscordNotificationService;
use Illuminate\Auth\Events\Registered;

class NotifyDiscordOnRegistration
{
    public function __construct(
        private readonly DiscordNotificationService $discordNotification,
    ) {}

    public function handle(Registered $event): void
    {
        $this->discordNotification->notifyNewUser($event->user);
    }
}
