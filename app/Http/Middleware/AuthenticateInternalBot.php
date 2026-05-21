<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateInternalBot
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Internal-Token');
        $expected = config('services.discord.internal_api_token');

        if (empty($expected) || ! hash_equals((string) $expected, (string) $token)) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
