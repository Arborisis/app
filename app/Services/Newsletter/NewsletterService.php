<?php

declare(strict_types=1);

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Support\Str;

class NewsletterService
{
    public function subscribe(string $email, ?string $source = null): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber) {
            // Re-subscribe if previously unsubscribed
            if ($subscriber->unsubscribed_at !== null) {
                $subscriber->update([
                    'unsubscribed_at' => null,
                    'subscribed_at' => now(),
                    'source' => $source ?? $subscriber->source,
                ]);
            }

            return $subscriber;
        }

        return NewsletterSubscriber::create([
            'email' => $email,
            'token' => $this->generateToken(),
            'source' => $source,
            'subscribed_at' => now(),
        ]);
    }

    public function unsubscribe(string $token): bool
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (! $subscriber || $subscriber->unsubscribed_at !== null) {
            return false;
        }

        $subscriber->update([
            'unsubscribed_at' => now(),
        ]);

        return true;
    }

    public function findByEmail(string $email): ?NewsletterSubscriber
    {
        return NewsletterSubscriber::where('email', $email)->first();
    }

    public function isSubscribed(string $email): bool
    {
        $subscriber = $this->findByEmail($email);

        return $subscriber !== null && $subscriber->isActive();
    }

    private function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (NewsletterSubscriber::where('token', $token)->exists());

        return $token;
    }
}
