<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendDiscordNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public readonly ?string $channelId,
        public readonly ?string $discordId,
        public readonly ?string $content,
        public readonly ?array $embed,
        public readonly ?array $mentions,
    ) {}

    public function handle(): void
    {
        $botHost = config('services.discord.bot_host', '127.0.0.1');
        $botPort = config('services.discord.bot_port', 3001);
        $token = config('services.discord.internal_api_token');

        if (! $token) {
            return;
        }

        if ($this->discordId) {
            Http::withHeaders(['X-Internal-Token' => $token])
                ->post("http://{$botHost}:{$botPort}/webhook/notification/user", [
                    'discordId' => $this->discordId,
                    'content' => $this->content,
                    'embed' => $this->embed,
                ]);
        } elseif ($this->channelId) {
            Http::withHeaders(['X-Internal-Token' => $token])
                ->post("http://{$botHost}:{$botPort}/webhook/notification", [
                    'channelId' => $this->channelId,
                    'content' => $this->content,
                    'embed' => $this->embed,
                    'mentions' => $this->mentions,
                ]);
        }
    }
}
