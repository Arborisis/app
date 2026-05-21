<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\SoundPublished;
use App\Services\Push\PushNotificationService;

class SendNewSoundPushNotification
{
    public function __construct(
        private readonly PushNotificationService $pushService
    ) {}

    public function handle(SoundPublished $event): void
    {
        $sound = $event->sound;

        $this->pushService->sendToAll(
            'Nouveau son sur Arborisis',
            $sound->title,
            route('sounds.show', $sound->slug)
        );
    }
}
