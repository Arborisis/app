@extends('mail.layouts.arborisis', ['subject' => "Confirmation de réception — {$ticket->ticket_number}"])

@section('content')
<!-- Hero icon -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="text-align: center; padding-bottom: 24px;">
            <div style="display: inline-block; width: 56px; height: 56px; background: linear-gradient(135deg, rgba(52, 211, 153, 0.1) 0%, rgba(52, 211, 153, 0.04) 100%); border-radius: 50%; text-align: center; line-height: 56px;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#34D399" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
            </div>
        </td>
    </tr>
</table>

<!-- Greeting -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding-bottom: 24px;">
            <h1 style="margin: 0; font-family: 'Cormorant', Georgia, serif; font-size: 32px; font-weight: 600; color: #F3F0E7; line-height: 1.2; letter-spacing: -0.01em; mso-line-height-rule: exactly;">
                Votre demande est bien arrivée
            </h1>
            <p style="margin: 12px 0 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 16px; color: #8FA68E; line-height: 1.6; mso-line-height-rule: exactly;">
                Bonjour {{ $ticket->name }}, nous avons bien reçu votre message. Notre équipe vous répondra dans les plus brefs délais.
            </p>
        </td>
    </tr>
</table>

<!-- Ticket number highlight -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 20px 24px; background-color: rgba(52, 211, 153, 0.06); border: 1px solid rgba(52, 211, 153, 0.12); border-radius: 14px;">
            <p style="margin: 0 0 4px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 11px; font-weight: 500; color: #34D399; letter-spacing: 0.1em; text-transform: uppercase; mso-line-height-rule: exactly;">Numéro de suivi</p>
            <p style="margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 20px; font-weight: 500; color: #34D399; letter-spacing: 0.02em; mso-line-height-rule: exactly;">{{ $ticket->ticket_number }}</p>
        </td>
    </tr>
</table>

@include('mail.components.spacer', ['height' => 24])

@include('mail.components.detail-card', ['items' => [
    ['label' => 'Type de demande', 'value' => e($ticket->type->label())],
    ['label' => 'Sujet', 'value' => e($ticket->subject)],
    ['label' => 'Votre message', 'value' => nl2br(e($ticket->message))],
]])

@include('mail.components.spacer', ['height' => 24])

@include('mail.components.cta-button', ['url' => url('/contact'), 'label' => 'Retour au site'])

<!-- Closing note -->
@include('mail.components.spacer', ['height' => 24])
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td>
            <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 13px; color: #8FA68E; line-height: 1.6; font-style: italic; mso-line-height-rule: exactly;">
                Vous pouvez répondre directement à cet e-mail : votre message sera ajouté automatiquement au suivi de votre demande.
            </p>
        </td>
    </tr>
</table>
@endsection
