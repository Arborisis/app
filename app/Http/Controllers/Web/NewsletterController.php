<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Newsletter\SubscribeRequest;
use App\Services\Newsletter\NewsletterMailService;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function __construct(
        private NewsletterService $newsletterService,
        private NewsletterMailService $mailService,
    ) {}

    public function subscribe(SubscribeRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        $subscriber = $this->newsletterService->subscribe($email, 'website');

        // Send welcome email only for new subscriptions, not re-subscriptions
        if ($subscriber->wasRecentlyCreated) {
            $this->mailService->sendWelcome($subscriber);
        }

        return back()->with('success', 'Vous êtes inscrit à notre newsletter.');
    }

    public function unsubscribe(string $token): Response
    {
        $success = $this->newsletterService->unsubscribe($token);

        return Inertia::render('Newsletter/Unsubscribe', [
            'success' => $success,
        ]);
    }
}
