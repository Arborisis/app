<!DOCTYPE html>
<html lang="fr" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Arborisis — L'archive sonore de la nature. Découvrez, partagez et préservez les sons du monde vivant.">
        <meta name="theme-color" content="#0B1220">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon -->
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
        <link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">

        <!-- PWA -->
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
        <link rel="apple-touch-icon" sizes="152x152" href="/pwa-icons/icon-192x192.png">
        <link rel="apple-touch-icon" sizes="167x167" href="/pwa-icons/icon-192x192.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Arborisis">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="application-name" content="Arborisis">

        <!-- Microsoft / Windows -->
        <meta name="msapplication-TileColor" content="#0B1220">
        <meta name="msapplication-TileImage" content="/pwa-icons/icon-192x192.png">
        <meta name="msapplication-config" content="none">
        <meta name="msapplication-navbutton-color" content="#0B1220">

        <!-- Safari pinned tab -->
        <link rel="mask-icon" href="/pwa-icons/icon.svg" color="#34D399">

        <!-- Format detection -->
        <meta name="format-detection" content="telephone=no">

        <!-- Icons for various sizes -->
        <link rel="icon" type="image/png" sizes="192x192" href="/pwa-icons/icon-192x192.png">
        <link rel="icon" type="image/png" sizes="512x512" href="/pwa-icons/icon-512x512.png">

        <title inertia>{{ config('app.name', 'Arborisis') }}</title>

        <!-- Preload critical font files (self-hosted, no external blocking request) -->
        <link rel="preload" as="font" href="/fonts/dm-sans-latin-400-normal.woff2" type="font/woff2" crossorigin>
        <link rel="preload" as="font" href="/fonts/dm-sans-latin-700-normal.woff2" type="font/woff2" crossorigin>
        <link rel="preload" as="font" href="/fonts/cormorant-latin-400-normal.woff2" type="font/woff2" crossorigin>
        <link rel="preload" as="font" href="/fonts/cormorant-latin-700-normal.woff2" type="font/woff2" crossorigin>

        <!-- Preload LCP image -->
        <link rel="preload" as="image" href="/images/hero-leaf.webp" type="image/webp" fetchpriority="high">

        <!-- Google tag (gtag.js) with Consent Mode v2 -->
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}

            // Default consent: denied until user choice
            gtag('consent', 'default', {
                'analytics_storage': 'denied',
                'ad_storage': 'denied',
                'ad_user_data': 'denied',
                'ad_personalization': 'denied'
            });

            gtag('js', new Date());
            gtag('config', 'G-TFNYXFKRZW');
        </script>
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-TFNYXFKRZW"></script>

        <!-- Scripts -->
        @routes
        @vite('resources/js/app.js')
        @inertiaHead
    </head>
    <body class="font-sans antialiased bg-arbor-night text-arbor-cream">
        @inertia
    </body>
</html>
