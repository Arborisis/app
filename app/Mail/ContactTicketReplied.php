<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactTicketReplied extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactTicket $ticket,
        public string $reply
    ) {}

    public function envelope(): Envelope
    {
        $hello = new Address('hello@arborisis.com', 'Arborisis');

        return new Envelope(
            subject: "Re: [{$this->ticket->ticket_number}] Votre demande — Réponse de l'équipe",
            from: $hello,
            replyTo: [$hello],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.contact.ticket-replied',
        );
    }
}
