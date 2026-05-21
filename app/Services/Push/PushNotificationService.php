<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\MessageSentReport;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.vapid.subject'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);
    }

    /**
     * @return array{success: int, failures: int, removed: int}
     */
    public function sendToAll(string $title, string $body, ?string $url = null): array
    {
        $subscriptions = PushSubscription::all();

        return $this->sendToSubscribers($subscriptions, $title, $body, $url);
    }

    /**
     * @param iterable<PushSubscription> $subscriptions
     * @return array{success: int, failures: int, removed: int}
     */
    public function sendToSubscribers(iterable $subscriptions, string $title, string $body, ?string $url = null): array
    {
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/pwa-icons/icon.svg',
            'badge' => '/pwa-icons/icon.svg',
            'url' => $url ?? '/',
            'timestamp' => now()->timestamp,
        ]);

        foreach ($subscriptions as $sub) {
            $this->webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh,
                    'authToken' => $sub->auth,
                ]),
                $payload
            );
        }

        $success = 0;
        $failures = 0;
        $removed = 0;

        /** @var MessageSentReport $report */
        foreach ($this->webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $success++;
                continue;
            }

            $failures++;

            $endpoint = $report->getEndpoint();
            $response = $report->getResponse();

            if ($response && in_array($response->getStatusCode(), [410, 404, 403], true)) {
                PushSubscription::where('endpoint', $endpoint)->delete();
                $removed++;
            }
        }

        return compact('success', 'failures', 'removed');
    }
}
