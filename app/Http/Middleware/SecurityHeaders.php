<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(self)');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if (! $headers->has('Content-Security-Policy-Report-Only')) {
            $headers->set('Content-Security-Policy-Report-Only', implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline'",
                "img-src 'self' data: blob: https:",
                "font-src 'self' data:",
                "media-src 'self' blob: https:",
                "connect-src 'self' https: wss:",
            ]));
        }

        return $response;
    }
}
