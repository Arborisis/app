<?php

declare(strict_types=1);

use App\Enums\ContactTicketReplySource;
use App\Enums\ContactTicketStatus;
use App\Enums\ContactTicketType;
use App\Models\ContactTicket;

function inboundTicket(): ContactTicket
{
    return ContactTicket::create([
        'ticket_number' => 'ARB-20260516-INB01',
        'type' => ContactTicketType::Support,
        'name' => 'Camille Dubois',
        'email' => 'camille@example.com',
        'subject' => 'Question sur un enregistrement',
        'message' => 'Je souhaite obtenir une information sur un son publié.',
        'status' => ContactTicketStatus::Resolved,
    ]);
}

it('adds an inbound customer email reply to the matching contact ticket', function () {
    config(['services.contact.inbound_mail_token' => 'inbound-secret']);

    $ticket = inboundTicket();

    $this->withToken('inbound-secret')
        ->postJson(route('api.inbound.contact-ticket-replies'), [
            'from' => 'Camille Dubois <camille@example.com>',
            'subject' => "Re: [{$ticket->ticket_number}] Votre demande",
            'text' => "Merci pour votre retour.\n\nLe dim. 16 mai, Arborisis a écrit :\n> Ancien message",
        ])
        ->assertOk()
        ->assertJson([
            'status' => 'accepted',
            'ticket_number' => $ticket->ticket_number,
        ]);

    $reply = $ticket->replies()->first();

    expect($reply)
        ->not->toBeNull()
        ->source->toBe(ContactTicketReplySource::Customer)
        ->reply->toBe('Merci pour votre retour.');

    expect($ticket->fresh())
        ->status->toBe(ContactTicketStatus::InProgress);
});

it('ignores inbound replies when the sender does not match the ticket email', function () {
    config(['services.contact.inbound_mail_token' => 'inbound-secret']);

    $ticket = inboundTicket();

    $this->withToken('inbound-secret')
        ->postJson(route('api.inbound.contact-ticket-replies'), [
            'from_email' => 'other@example.com',
            'subject' => "Re: [{$ticket->ticket_number}] Votre demande",
            'text' => 'Je tente de répondre à ce ticket.',
        ])
        ->assertAccepted()
        ->assertJson(['status' => 'ignored']);

    expect($ticket->replies()->count())->toBe(0);
});

it('rejects inbound replies without the configured token', function () {
    config(['services.contact.inbound_mail_token' => 'inbound-secret']);

    $this->postJson(route('api.inbound.contact-ticket-replies'), [
        'from_email' => 'camille@example.com',
        'subject' => '[ARB-20260516-INB01] Votre demande',
        'text' => 'Merci.',
    ])->assertForbidden();
});
