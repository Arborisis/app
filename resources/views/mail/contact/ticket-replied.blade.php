@extends('mail.layouts.arborisis', ['subject' => "Réponse à votre demande — {$ticket->ticket_number}"])

@section('content')
<!-- Greeting -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding-bottom: 24px;">
            <h1 style="margin: 0; font-family: 'Cormorant', Georgia, serif; font-size: 32px; font-weight: 600; color: #F3F0E7; line-height: 1.2; letter-spacing: -0.01em; mso-line-height-rule: exactly;">
                Une réponse vous attend
            </h1>
            <p style="margin: 12px 0 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 16px; color: #8FA68E; line-height: 1.6; mso-line-height-rule: exactly;">
                Bonjour {{ $ticket->name }}, notre équipe a répondu à votre demande <strong style="color: #34D399; font-weight: 500;">{{ $ticket->ticket_number }}</strong>.
            </p>
        </td>
    </tr>
</table>

<!-- Reply box -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 24px; background-color: rgba(52, 211, 153, 0.04); border-left: 3px solid #34D399; border-radius: 0 14px 14px 0;">
            <p style="margin: 0 0 12px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 11px; font-weight: 500; color: #34D399; letter-spacing: 0.1em; text-transform: uppercase; mso-line-height-rule: exactly;">Réponse de l'équipe</p>
            <div style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 15px; color: #F3F0E7; line-height: 1.7; mso-line-height-rule: exactly;">
                {!! nl2br(e($reply)) !!}
            </div>
        </td>
    </tr>
</table>

@include('mail.components.spacer', ['height' => 24])

<!-- Original request summary -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 20px 24px; background-color: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05); border-radius: 14px;">
            <p style="margin: 0 0 8px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 11px; font-weight: 500; color: #8FA68E; letter-spacing: 0.08em; text-transform: uppercase; mso-line-height-rule: exactly;">Votre demande originale</p>
            <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 14px; color: #E8E4D9; line-height: 1.5; mso-line-height-rule: exactly;"><strong style="color: #F3F0E7;">{{ $ticket->subject }}</strong></p>
            <p style="margin: 8px 0 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 13px; color: #8FA68E; line-height: 1.5; mso-line-height-rule: exactly;">{{ Str::limit($ticket->message, 200) }}</p>
        </td>
    </tr>
</table>

@include('mail.components.spacer', ['height' => 24])

@include('mail.components.cta-button', ['url' => url('/contact'), 'label' => 'Retour au site'])

<!-- Reply note -->
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
