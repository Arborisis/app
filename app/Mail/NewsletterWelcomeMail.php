<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bienvenue dans la communauté Arborisis',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.newsletter.welcome',
            with: [
                'unsubscribeUrl' => route('newsletter.unsubscribe', ['token' => $this->subscriber->token]),
            ],
        );
    }
}
