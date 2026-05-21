<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyRadioInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-Internal-Token') ?: $request->query('token');
        $expected = config('radio.internal_token');

        if (empty($expected) || ! hash_equals((string) $expected, (string) $token)) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
