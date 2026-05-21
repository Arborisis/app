<?php

declare(strict_types=1);

namespace App\Services\Radio;

use App\Enums\RadioListenerSessionStatus;
use App\Models\RadioListenerSession;
use Illuminate\Support\Facades\DB;

class ListenerSessionTracker
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function start(string $token, array $meta = []): RadioListenerSession
    {
        return RadioListenerSession::query()->updateOrCreate(
            ['session_token' => $token],
            [
                'channel_id' => $meta['channel_id'] ?? null,
                'user_id' => $meta['user_id'] ?? null,
                'ip_hash' => $meta['ip_hash'] ?? hash('sha256', (string) ($meta['ip'] ?? '')),
                'ua_hash' => $meta['ua_hash'] ?? hash('sha256', (string) ($meta['user_agent'] ?? '')),
                'country' => $meta['country'] ?? null,
                'started_at' => now(),
                'last_heartbeat_at' => now(),
                'ended_at' => null,
                'status' => RadioListenerSessionStatus::Active,
            ],
        );
    }

    public function heartbeat(string $token, int $bytesStreamed = 0): void
    {
        RadioListenerSession::query()
            ->where('session_token', $token)
            ->update([
                'last_heartbeat_at' => now(),
                'bytes_streamed' => DB::raw('bytes_streamed + '.max(0, $bytesStreamed)),
                'status' => RadioListenerSessionStatus::Active,
            ]);
    }

    public function close(string $token): void
    {
        RadioListenerSession::query()
            ->where('session_token', $token)
            ->update([
                'ended_at' => now(),
                'status' => RadioListenerSessionStatus::Closed,
            ]);
    }

    public function purgeExpired(int $ttlSeconds): int
    {
        return RadioListenerSession::query()
            ->expiredSince($ttlSeconds)
            ->update([
                'ended_at' => now(),
                'status' => RadioListenerSessionStatus::Expired,
            ]);
    }

    public function activeCount(): int
    {
        return RadioListenerSession::query()->active()->count();
    }
}
