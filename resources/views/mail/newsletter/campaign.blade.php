@extends('mail.layouts.arborisis', ['subject' => $campaign->subject])

@section('content')
<!-- Campaign content -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding-bottom: 8px;">
            <h1 style="margin: 0; font-family: 'Cormorant', Georgia, serif; font-size: 32px; font-weight: 600; color: #F3F0E7; line-height: 1.2; letter-spacing: -0.01em; mso-line-height-rule: exactly;">
                {{ $campaign->subject }}
            </h1>
        </td>
    </tr>
</table>

<!-- Decorative line -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 16px 0 24px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="60">
                <tr>
                    <td style="height: 2px; background-color: #34D399; font-size: 0; line-height: 0; mso-line-height-rule: exactly;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Content -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="font-family: 'DM Sans', system-ui, sans-serif; font-size: 16px; color: #E8E4D9; line-height: 1.7; mso-line-height-rule: exactly;">
            {!! $campaign->content_html !!}
        </td>
    </tr>
</table>

@include('mail.components.spacer', ['height' => 32])

<!-- Unsubscribe note -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.06);">
            <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 12px; color: #8FA68E; opacity: 0.7; line-height: 1.5; text-align: center; mso-line-height-rule: exactly;">
                Vous recevez cet e-mail car vous êtes abonné à la newsletter Arborisis.
                <br>
                <a href="{{ $unsubscribeUrl }}" style="color: #8FA68E; text-decoration: underline;">Se désinscrire</a>
            </p>
        </td>
    </tr>
</table>
@endsection
