<?php

declare(strict_types=1);

namespace App\Services\Newsletter;

use App\Enums\NewsletterCampaignStatus;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterWelcomeMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;

class NewsletterMailService
{
    public function __construct(
        private NewsletterService $newsletterService,
    ) {}

    public function sendWelcome(NewsletterSubscriber $subscriber): void
    {
        Mail::to($subscriber->email)->queue(new NewsletterWelcomeMail($subscriber));
    }

    public function sendCampaign(NewsletterCampaign $campaign): void
    {
        $campaign->update([
            'status' => NewsletterCampaignStatus::Sending,
        ]);

        $subscribers = NewsletterSubscriber::active()->get();
        $count = 0;

        foreach ($subscribers->chunk(50) as $chunk) {
            foreach ($chunk as $subscriber) {
                Mail::to($subscriber->email)->queue(
                    new NewsletterCampaignMail($campaign, $subscriber)
                );
                $count++;
            }
        }

        $campaign->update([
            'status' => NewsletterCampaignStatus::Sent,
            'sent_at' => now(),
            'recipients_count' => $count,
        ]);
    }
}
