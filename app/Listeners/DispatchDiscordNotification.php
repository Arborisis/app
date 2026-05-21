<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DiscordNotification;
use App\Jobs\SendDiscordNotificationJob;

class DispatchDiscordNotification
{
    public function handle(DiscordNotification $event): void
    {
        SendDiscordNotificationJob::dispatch(
            $event->channelId,
            $event->discordId,
            $event->content,
            $event->embed,
            $event->mentions,
        )->onQueue('discord');
    }
}
