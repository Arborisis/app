<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsModerator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->isModerator()) {
            abort(403, 'Accès réservé aux administrateurs et modérateurs.');
        }

        return $next($request);
    }
}
