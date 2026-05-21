<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscordNotification
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?string $channelId = null,
        public readonly ?string $discordId = null,
        public readonly ?string $content = null,
        public readonly ?array $embed = null,
        public readonly ?array $mentions = null,
    ) {}
}
