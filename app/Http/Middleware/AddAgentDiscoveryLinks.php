<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddAgentDiscoveryLinks
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET') || $request->path() !== '/') {
            return $response;
        }

        $response->headers->set('Link', [
            '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"',
            '</openapi.json>; rel="service-desc"; type="application/vnd.oai.openapi+json"',
            '</docs/api>; rel="service-doc"; type="text/html"',
            '</api/health>; rel="status"; type="application/json"',
            '</.well-known/oauth-authorization-server>; rel="oauth-authorization-server"; type="application/json"',
            '</.well-known/oauth-protected-resource>; rel="oauth-protected-resource"; type="application/json"',
            '</.well-known/mcp/server-card.json>; rel="mcp-server-card"; type="application/json"',
            '</.well-known/agent-skills/index.json>; rel="agent-skills"; type="application/json"',
        ], false);

        return $response;
    }
}
