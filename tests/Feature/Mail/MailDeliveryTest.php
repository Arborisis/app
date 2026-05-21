<?php

declare(strict_types=1);

use App\Enums\ContactTicketStatus;
use App\Enums\ContactTicketType;
use App\Enums\NewsletterCampaignStatus;
use App\Mail\ContactTicketReceived;
use App\Mail\ContactTicketReplied;
use App\Mail\ContactTicketSubmitted;
use App\Mail\NewsletterCampaignMail;
use App\Mail\NewsletterWelcomeMail;
use App\Models\ContactTicket;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Services\Contact\ContactTicketService;
use App\Services\Newsletter\NewsletterMailService;
use Illuminate\Support\Facades\Mail;

function mailTestTicket(): ContactTicket
{
    return ContactTicket::create([
        'ticket_number' => 'ARB-20260516-TEST1',
        'type' => ContactTicketType::Support,
        'name' => 'Camille Dubois',
        'email' => 'camille@example.com',
        'subject' => 'Question sur un enregistrement',
        'message' => "Bonjour,\nJe souhaite obtenir une information sur un son publié.",
        'status' => ContactTicketStatus::New,
    ]);
}

it('queues contact notification and receipt emails when a ticket is created', function () {
    Mail::fake();

    app(ContactTicketService::class)->create([
        'type' => ContactTicketType::Support->value,
        'name' => 'Camille Dubois',
        'email' => 'camille@example.com',
        'subject' => 'Question sur un enregistrement',
        'message' => 'Je souhaite obtenir une information sur un son publié.',
    ], null);

    Mail::assertQueued(ContactTicketSubmitted::class, fn (ContactTicketSubmitted $mail) => $mail->hasTo('contact@arborisis.com'));
    Mail::assertQueued(ContactTicketReceived::class, fn (ContactTicketReceived $mail) => $mail->hasTo('camille@example.com'));
});

it('queues newsletter welcome email only for a new subscriber', function () {
    Mail::fake();

    $this->withSession(['_token' => 'test'])->post(route('newsletter.subscribe'), [
        'email' => 'listener@example.com',
        '_token' => 'test',
    ])->assertRedirect();

    Mail::assertQueued(NewsletterWelcomeMail::class, fn (NewsletterWelcomeMail $mail) => $mail->hasTo('listener@example.com'));
});

it('queues one campaign email per active newsletter subscriber', function () {
    Mail::fake();

    NewsletterSubscriber::factory()->count(2)->create();
    NewsletterSubscriber::factory()->unsubscribed()->create();

    $campaign = NewsletterCampaign::create([
        'subject' => 'Nouveaux paysages sonores',
        'content_html' => '<p>Une sélection de captations naturelles vient de paraître.</p>',
        'content_text' => 'Une sélection de captations naturelles vient de paraître.',
        'status' => NewsletterCampaignStatus::Draft,
    ]);

    app(NewsletterMailService::class)->sendCampaign($campaign);

    Mail::assertQueued(NewsletterCampaignMail::class, 2);
    expect($campaign->fresh())
        ->status->toBe(NewsletterCampaignStatus::Sent)
        ->recipients_count->toBe(2);
});

it('renders all custom mail templates with Arborisis branding', function () {
    $ticket = mailTestTicket();
    $subscriber = NewsletterSubscriber::factory()->create();
    $user = User::factory()->create();
    $campaign = NewsletterCampaign::create([
        'subject' => 'Nouveaux paysages sonores',
        'content_html' => '<p>Une sélection de captations naturelles vient de paraître.</p>',
        'content_text' => 'Une sélection de captations naturelles vient de paraître.',
        'status' => NewsletterCampaignStatus::Draft,
    ]);

    $rendered = [
        (new ContactTicketSubmitted($ticket))->render(),
        (new ContactTicketReceived($ticket))->render(),
        (new ContactTicketReplied($ticket, 'Merci pour votre message, nous revenons vers vous.'))->render(),
        (new NewsletterWelcomeMail($subscriber))->render(),
        (new NewsletterCampaignMail($campaign, $subscriber))->render(),
        (new VerifyEmailNotification())->toMail($user)->render(),
        (new ResetPasswordNotification('reset-token'))->toMail($user)->render(),
    ];

    foreach ($rendered as $html) {
        expect($html)
            ->toContain('Arborisis')
            ->toContain('Field Recording & Nature Sounds')
            ->toContain('Plateforme sociale de field recording');
    }
});

it('does not set the customer as reply-to on contact reply emails', function () {
    $reply = new ContactTicketReplied(mailTestTicket(), 'Votre demande a ete traitee.');

    expect($reply->envelope()->replyTo[0]->address)->toBe('hello@arborisis.com');
});

it('sends customer contact emails from hello address', function () {
    $ticket = mailTestTicket();

    expect((new ContactTicketReceived($ticket))->envelope())
        ->from->address->toBe('hello@arborisis.com')
        ->replyTo->{0}->address->toBe('hello@arborisis.com');

    expect((new ContactTicketReplied($ticket, 'Votre demande a ete traitee.'))->envelope())
        ->from->address->toBe('hello@arborisis.com')
        ->replyTo->{0}->address->toBe('hello@arborisis.com');
});

it('tells contact customers they can reply directly to ticket emails', function () {
    $ticket = mailTestTicket();

    $received = (new ContactTicketReceived($ticket))->render();
    $replied = (new ContactTicketReplied($ticket, 'Votre demande a ete traitee.'))->render();

    expect($received)
        ->not->toContain('Merci de ne pas répondre directement à cet e-mail')
        ->toContain('Vous pouvez répondre directement à cet e-mail');

    expect($replied)
        ->toContain('Vous pouvez répondre directement à cet e-mail');
});
