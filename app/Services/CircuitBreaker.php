<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    /**
     * @template T
     * @param  callable(): T  $action
     * @param  callable(): T|T|null  $fallback
     * @return T|null
     */
    public function attempt(string $key, callable $action, mixed $fallback = null): mixed
    {
        if ($this->isOpen($key)) {
            return is_callable($fallback) ? $fallback() : $fallback;
        }

        try {
            $result = $action();
            $this->recordSuccess($key);

            return $result;
        } catch (\Throwable $e) {
            $this->recordFailure($key);

            if (is_callable($fallback)) {
                return $fallback();
            }

            if ($fallback !== null) {
                return $fallback;
            }

            throw $e;
        }
    }

    public function state(string $key): string
    {
        if ($this->isOpen($key)) {
            return 'open';
        }

        return ((int) Cache::get($this->failureKey($key), 0)) > 0 ? 'half_open' : 'closed';
    }

    public function recordFailure(string $key): void
    {
        $failures = (int) Cache::increment($this->failureKey($key));
        Cache::put($this->failureKey($key), $failures, now()->addSeconds($this->recoverySeconds($key)));

        if ($failures >= $this->threshold($key)) {
            Cache::put($this->openKey($key), true, now()->addSeconds($this->recoverySeconds($key)));
        }
    }

    public function recordSuccess(string $key): void
    {
        Cache::forget($this->failureKey($key));
        Cache::forget($this->openKey($key));
    }

    private function isOpen(string $key): bool
    {
        return (bool) Cache::get($this->openKey($key), false);
    }

    private function threshold(string $key): int
    {
        return max(1, (int) config("radio.circuit_breaker.{$key}.failure_threshold", 5));
    }

    private function recoverySeconds(string $key): int
    {
        return max(10, (int) config("radio.circuit_breaker.{$key}.recovery_seconds", 120));
    }

    private function failureKey(string $key): string
    {
        return "circuit-breaker:{$key}:failures";
    }

    private function openKey(string $key): string
    {
        return "circuit-breaker:{$key}:open";
    }
}
