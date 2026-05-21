@extends('mail.layouts.arborisis', ['subject' => 'Réinitialisez votre mot de passe'])

@section('content')
<!-- Hero icon -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="text-align: center; padding-bottom: 24px;">
            <div style="display: inline-block; width: 64px; height: 64px; background: linear-gradient(135deg, rgba(212, 165, 116, 0.12) 0%, rgba(52, 211, 153, 0.08) 100%); border-radius: 50%; text-align: center; line-height: 64px;">
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D4A574" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: middle;">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
        </td>
    </tr>
</table>

<!-- Greeting -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="text-align: center; padding-bottom: 24px;">
            <h1 style="margin: 0; font-family: 'Cormorant', Georgia, serif; font-size: 32px; font-weight: 600; color: #F3F0E7; line-height: 1.2; letter-spacing: -0.01em; mso-line-height-rule: exactly;">
                Réinitialisez votre mot de passe
            </h1>
            <p style="margin: 12px 0 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 16px; color: #8FA68E; line-height: 1.6; mso-line-height-rule: exactly;">
                Bonjour {{ $user->name ?? 'explorateur' }}, nous avons reçu une demande de réinitialisation pour votre compte Arborisis.
            </p>
        </td>
    </tr>
</table>

<!-- Divider -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 8px 0 24px; text-align: center;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="60" style="margin: 0 auto;">
                <tr>
                    <td style="height: 2px; background-color: #34D399; font-size: 0; line-height: 0; mso-line-height-rule: exactly;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Body -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding-bottom: 24px;">
            <p style="margin: 0 0 16px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 16px; color: #E8E4D9; line-height: 1.7; mso-line-height-rule: exactly;">
                Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe. Ce lien est valable pendant <strong style="color: #F3F0E7;">{{ $expires }} minutes</strong>.
            </p>
        </td>
    </tr>
</table>

@include('mail.components.spacer', ['height' => 8])

<!-- CTA -->
@include('mail.components.cta-button', ['url' => $url, 'label' => 'Choisir un nouveau mot de passe'])

@include('mail.components.spacer', ['height' => 24])

<!-- Fallback link -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="text-align: center;">
            <p style="margin: 0 0 8px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 12px; color: #8FA68E; line-height: 1.5; mso-line-height-rule: exactly;">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :
            </p>
            <p style="margin: 0; font-family: 'JetBrains Mono', monospace; font-size: 11px; color: #8FA68E; line-height: 1.5; word-break: break-all; mso-line-height-rule: exactly;">
                {{ $url }}
            </p>
        </td>
    </tr>
</table>

@include('mail.components.spacer', ['height' => 24])

<!-- Security note -->
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
        <td style="padding: 16px 20px; background-color: rgba(212, 165, 116, 0.04); border: 1px solid rgba(212, 165, 116, 0.1); border-radius: 12px;">
            <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 13px; color: #D4A574; line-height: 1.6; mso-line-height-rule: exactly;">
                Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet e-mail. Votre mot de passe actuel restera inchangé et sécurisé.
            </p>
        </td>
    </tr>
</table>
@endsection
