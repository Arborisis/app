<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar_url' => $user->avatar_url,
                    'level' => $user->level,
                    'xp_total' => $user->xp_total,
                    'is_moderator' => $user->isModerator(),
                ] : null,
            ],
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy())->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    public function handle(Request $request, \Closure $next)
    {
        $response = parent::handle($request, $next);

        // Add cache headers for static assets
        if ($request->is('build/*', 'fonts/*', 'images/*', 'css/*', 'js/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
            $response->headers->set('Vary', 'Accept-Encoding');
        }

        return $response;
    }
}
