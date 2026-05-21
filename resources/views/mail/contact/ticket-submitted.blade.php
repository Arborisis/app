@extends('mail.layouts.arborisis', ['subject' => "Nouveau ticket — {$ticket->ticket_number}"])

@section('content')
@include('mail.components.alert-banner', ['type' => 'warning', 'message' => 'Nouveau ticket de contact reçu'])

@include('mail.components.spacer', ['height' => 16])

<!-- Ticket number -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding-bottom: 24px;">
            <h1 style="margin: 0; font-family: 'Cormorant', Georgia, serif; font-size: 32px; font-weight: 600; color: #F3F0E7; line-height: 1.2; letter-spacing: -0.01em; mso-line-height-rule: exactly;">
                Ticket {{ $ticket->ticket_number }}
            </h1>
            <p style="margin: 8px 0 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 14px; color: #8FA68E; mso-line-height-rule: exactly;">
                Créé le {{ $ticket->created_at->format('d/m/Y à H:i') }}
            </p>
        </td>
    </tr>
</table>

@include('mail.components.detail-card', ['items' => [
    ['label' => 'Type', 'value' => '<span style="display: inline-block; padding: 4px 12px; background-color: rgba(52, 211, 153, 0.1); color: #34D399; border-radius: 9999px; font-size: 13px; font-weight: 500;">' . e($ticket->type->label()) . '</span>'],
    ['label' => 'De', 'value' => e($ticket->name) . ' &lt;' . e($ticket->email) . '&gt;'],
    ['label' => 'Sujet', 'value' => e($ticket->subject)],
    ['label' => 'Message', 'value' => nl2br(e($ticket->message))],
]])

@include('mail.components.spacer', ['height' => 24])

<!-- CTA -->
@include('mail.components.cta-button', ['url' => url('/admin/contact-tickets/' . $ticket->id . '/edit'), 'label' => 'Gérer le ticket'])
@endsection
