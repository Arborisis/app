<?php

declare(strict_types=1);

use App\Services\CircuitBreaker;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    config([
        'radio.circuit_breaker.test.failure_threshold' => 2,
        'radio.circuit_breaker.test.recovery_seconds' => 60,
    ]);
});

it('opens after the configured number of failures and returns fallback', function () {
    $breaker = new CircuitBreaker();

    expect(fn () => $breaker->attempt('test', fn () => throw new RuntimeException('one')))->toThrow(RuntimeException::class);
    expect(fn () => $breaker->attempt('test', fn () => throw new RuntimeException('two')))->toThrow(RuntimeException::class);

    expect($breaker->state('test'))->toBe('open');
    expect($breaker->attempt('test', fn () => 'not called', 'fallback'))->toBe('fallback');
});

it('clears failures after a successful attempt', function () {
    $breaker = new CircuitBreaker();

    expect(fn () => $breaker->attempt('test', fn () => throw new RuntimeException('one')))->toThrow(RuntimeException::class);
    expect($breaker->state('test'))->toBe('half_open');

    expect($breaker->attempt('test', fn () => 'ok'))->toBe('ok');
    expect($breaker->state('test'))->toBe('closed');
});
