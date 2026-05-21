<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PerformanceHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Performance headers for HTML responses
        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            // Enable early hints for HTTP/2
            $response->headers->set('Accept-CH', 'Sec-CH-UA-Platform-Version, Sec-CH-UA-Model');
        }

        return $response;
    }
}
