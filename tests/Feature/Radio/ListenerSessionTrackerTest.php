<?php

declare(strict_types=1);

use App\Enums\RadioListenerSessionStatus;
use App\Models\RadioListenerSession;
use App\Services\Radio\ListenerSessionTracker;

it('starts heartbeats closes and expires listener sessions', function () {
    $tracker = new ListenerSessionTracker();

    $session = $tracker->start('listener-1', ['ip' => '127.0.0.1', 'user_agent' => 'test']);

    expect($session->status)->toBe(RadioListenerSessionStatus::Active);
    expect($tracker->activeCount())->toBe(1);

    $tracker->heartbeat('listener-1', 512);
    expect(RadioListenerSession::query()->first()->bytes_streamed)->toBe(512);

    $tracker->close('listener-1');
    expect(RadioListenerSession::query()->first()->status)->toBe(RadioListenerSessionStatus::Closed);
    expect($tracker->activeCount())->toBe(0);

    $tracker->start('listener-2');
    RadioListenerSession::query()
        ->where('session_token', 'listener-2')
        ->update(['last_heartbeat_at' => now()->subMinutes(10)]);

    expect($tracker->purgeExpired(60))->toBe(1);
    expect(RadioListenerSession::query()->where('session_token', 'listener-2')->first()->status)
        ->toBe(RadioListenerSessionStatus::Expired);
});
