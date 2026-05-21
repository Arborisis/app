<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyInternalApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $expected = config('services.analyzer.internal_api_token');

        if (empty($expected) || ! hash_equals((string) $expected, (string) $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
