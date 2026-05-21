<?php

declare(strict_types=1);

namespace App\Services\Storage;

use Illuminate\Support\Facades\Storage;

class SignedUrlService
{
    public function __construct(
        private readonly string $signingKey,
        private readonly string $customDomain,
    ) {}

    public function url(string $disk, string $path, int $ttlMinutes = 60): string
    {
        if ($disk !== 'r2') {
            return Storage::disk($disk)->url($path);
        }

        $expires = (int) now()->addMinutes($ttlMinutes)->getTimestamp();
        $signature = rawurlencode($this->sign($path, $expires));

        return "{$this->customDomain}/{$path}?expires={$expires}&signature={$signature}";
    }

    private function sign(string $path, int $expires): string
    {
        $payload = "{$path}:{$expires}";

        return base64_encode(hash_hmac('sha256', $payload, $this->signingKey, true));
    }
}
