<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="dark">
    <meta name="supported-color-schemes" content="dark">
    <title>{{ $subject ?? 'Arborisis' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style type="text/css">
        /* Reset */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; border-collapse: collapse; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; display: block; }

        /* Outlook */
        .email-bg { mso-line-height-rule: exactly; }
        .body-bg { mso-line-height-rule: exactly; }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .container { width: 100% !important; max-width: 100% !important; }
            .mobile-padding { padding-left: 20px !important; padding-right: 20px !important; }
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-center { text-align: center !important; }
            .mobile-hide { display: none !important; }
            h1 { font-size: 26px !important; line-height: 1.2 !important; }
            h2 { font-size: 20px !important; line-height: 1.3 !important; }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .email-bg { background-color: #0a0f1a !important; }
            .body-bg { background-color: #0B1220 !important; }
        }
    </style>
</head>
<body class="email-bg" style="margin: 0; padding: 0; background-color: #070a10; font-family: 'DM Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; color: #F3F0E7; -webkit-font-smoothing: antialiased; mso-line-height-rule: exactly;">
    <!-- Outer wrapper table -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-bg" style="background-color: #070a10;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!-- Main container -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" class="container body-bg" style="max-width: 600px; width: 100%; background-color: #0B1220; border-radius: 20px; overflow: hidden; border: 1px solid rgba(255,255,255,0.06);">
                    <!-- Ambient top glow -->
                    <tr>
                        <td style="background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(74, 103, 65, 0.25), transparent 70%); height: 120px; padding: 0; mso-line-height-rule: exactly;">
                            <!-- Header -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="padding: 32px 40px 24px;">
                                <tr>
                                    <td class="mobile-padding" style="padding: 32px 40px 24px;">
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td style="text-align: center;">
                                                    <!-- Arborisis Leaf Logo SVG -->
                                                    <div style="margin-bottom: 16px;">
                                                        <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: inline-block;">
                                                            <circle cx="24" cy="24" r="23" stroke="rgba(52,211,153,0.15)" stroke-width="1"/>
                                                            <path d="M24 8C24 8 12 17 12 27C12 33.627 17.373 39 24 39C30.627 39 36 33.627 36 27C36 17 24 8 24 8Z" stroke="#34D399" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                                                            <path d="M24 39V20" stroke="#34D399" stroke-width="1.5" stroke-linecap="round"/>
                                                            <path d="M24 26C24 26 18 23.5 16.5 18" stroke="#34D399" stroke-width="1.5" stroke-linecap="round"/>
                                                            <path d="M24 28.5C24 28.5 30 26 31.5 21" stroke="#34D399" stroke-width="1.5" stroke-linecap="round"/>
                                                            <path d="M24 32V35" stroke="#34D399" stroke-width="1.5" stroke-linecap="round"/>
                                                        </svg>
                                                    </div>
                                                    <!-- Brand -->
                                                    <h1 style="margin: 0; font-family: 'Cormorant', Georgia, 'Times New Roman', serif; font-size: 28px; font-weight: 600; color: #F3F0E7; letter-spacing: -0.01em; line-height: 1.2; mso-line-height-rule: exactly;">
                                                        Arborisis
                                                    </h1>
                                                    <p style="margin: 6px 0 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 11px; font-weight: 500; color: #8FA68E; letter-spacing: 0.12em; text-transform: uppercase; mso-line-height-rule: exactly;">
                                                        Field Recording & Nature Sounds
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="border-top: 1px solid rgba(255,255,255,0.08); font-size: 0; line-height: 0; mso-line-height-rule: exactly;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="mobile-padding" style="padding: 32px 40px 40px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Bottom accent line -->
                    <tr>
                        <td style="padding: 0 40px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="height: 2px; background: linear-gradient(90deg, transparent 0%, #34D399 30%, #D4A574 70%, transparent 100%); font-size: 0; line-height: 0; mso-line-height-rule: exactly;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="mobile-padding" style="padding: 32px 40px 40px; background-color: rgba(17, 24, 39, 0.6);">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 8px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 13px; color: #8FA68E; line-height: 1.6; mso-line-height-rule: exactly;">
                                            Arborisis — Plateforme sociale de field recording
                                        </p>
                                        <p style="margin: 0 0 16px; font-family: 'DM Sans', system-ui, sans-serif; font-size: 12px; color: #8FA68E; opacity: 0.7; line-height: 1.5; mso-line-height-rule: exactly;">
                                            Vous recevez cet e-mail car vous utilisez notre plateforme.
                                        </p>
                                        @if(isset($unsubscribeUrl))
                                        <p style="margin: 0 0 8px;">
                                            <a href="{{ $unsubscribeUrl }}" style="font-family: 'DM Sans', system-ui, sans-serif; font-size: 12px; color: #8FA68E; text-decoration: underline; opacity: 0.7; mso-line-height-rule: exactly;">Se désinscrire de la newsletter</a>
                                        </p>
                                        @endif
                                        <p style="margin: 16px 0 0;">
                                            <a href="{{ url('/') }}" style="display: inline-block; font-family: 'DM Sans', system-ui, sans-serif; font-size: 12px; color: #34D399; text-decoration: none; font-weight: 500; mso-line-height-rule: exactly;">Visiter le site →</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
                <!-- End container -->

                <!-- Legal micro-footer -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; width: 100%; margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; padding: 0 20px;">
                            <p style="margin: 0; font-family: 'DM Sans', system-ui, sans-serif; font-size: 11px; color: #8FA68E; opacity: 0.5; line-height: 1.5; mso-line-height-rule: exactly;">
                                © {{ date('Y') }} Arborisis. Tous droits réservés.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
